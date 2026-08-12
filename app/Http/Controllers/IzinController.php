<?php

namespace App\Http\Controllers;

use App\Models\Izin;
use App\Models\User;
use App\Models\Absensi;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class IzinController extends Controller
{
    protected $wa;

    public function __construct(FonnteService $wa)
    {
        $this->wa = $wa;
    }

    public function index()
    {
        $user = Auth::user();
        $users = [];
        if ($user->isYayasan() || $user->isAdminUnit()) {
            $izins = Izin::with('user')
                ->where('jenis_izin', '!=', 'Libur')
                ->when($user->isAdminUnit(), function($q) use ($user) {
                    return $q->whereHas('user', function($qu) use ($user) {
                        $qu->where('unit', $user->unit);
                    });
                })
                ->orderBy('created_at', 'desc');

            $users = User::where('role', 'pegawai')
                ->orderBy('name')
                ->when($user->isAdminUnit(), function($q) use ($user) {
                    return $q->where('unit', $user->unit);
                })
                ->get();
        } else {
            $izins = Izin::where('user_id', $user->id)
                ->where('jenis_izin', '!=', 'Libur')
                ->orderBy('created_at', 'desc');
        }

        if (request('status')) {
            $izins->where('status', request('status'));
        }

        if (request('search')) {
            $izins->whereHas('user', function($q) {
                $q->where('name', 'like', '%'.request('search').'%');
            });
        }

        $izins = $izins->paginate(10);

        return view('kepegawaian.izin.index', compact('izins', 'users'));
    }

    /**
     * Sinkronisasi data izin ke tabel absensi setelah disetujui.
     */
    protected function syncToAbsensi(Izin $izin)
    {
        if ($izin->status !== 'Disetujui') return;

        // Tentukan status kehadiran berdasarkan jenis izin
        // 'SAKIT' -> Sakit, Sisanya -> Izin (kecuali Cuti jika ada di masa depan)
        $jenis = strtoupper($izin->jenis_izin);
        $statusAbsensi = 'Izin';
        if ($jenis === 'SAKIT') $statusAbsensi = 'Sakit';
        if ($jenis === 'LIBUR') $statusAbsensi = 'Libur';
        if ($jenis === 'CUTI') $statusAbsensi = 'Cuti'; // In case you add it later
        
        $start = Carbon::parse($izin->tgl_mulai);
        $end = $izin->tgl_selesai ? Carbon::parse($izin->tgl_selesai) : $start->copy();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $tgl = $date->toDateString();
            
            Absensi::updateOrCreate(
                ['user_id' => $izin->user_id, 'tgl' => $tgl],
                [
                    'status_kehadiran' => $statusAbsensi,
                    'keterangan' => $izin->alasan . " (" . $izin->jenis_izin . ")",
                    'pin' => $izin->user->pin_fingerspot ?? 'IZIN_SYSTEM',
                ]
            );
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis_izin' => 'required',
            'tgl_mulai' => 'required|date',
            'alasan' => 'required',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        $currentUser = Auth::user();
        $isMasal = $request->has('is_masal') && ($currentUser->isYayasan() || $currentUser->isAdminUnit());

        if ($isMasal) {
            // Libur Masal Logic
            $targetUsers = User::whereIn('role', ['pegawai', 'admin_unit', 'yayasan'])
                ->when($currentUser->isAdminUnit(), function($q) use ($currentUser) {
                    return $q->where('unit', $currentUser->unit);
                })
                ->get();

            foreach ($targetUsers as $targetUser) {
                $data = $request->except(['lampiran', 'is_masal', 'user_id']);
                $data['user_id'] = $targetUser->id;
                $data['status'] = 'Disetujui';
                
                if ($request->hasFile('lampiran')) {
                    $path = $request->file('lampiran')->store('public/lampiran_izin');
                    $data['lampiran'] = $path;
                }

                $izin = Izin::create($data);
                $this->syncToAbsensi($izin);
            }

            return redirect()->back()->with('success', 'Libur masal berhasil diterapkan ke ' . $targetUsers->count() . ' pegawai.');
        }

        // Individual Izin Logic
        $data = $request->except('lampiran');
        
        if (($currentUser->isYayasan() || $currentUser->isAdminUnit()) && $request->user_id) {
            // Security check for Admin Unit
            if ($currentUser->isAdminUnit()) {
                $checkUser = User::findOrFail($request->user_id);
                if ($checkUser->unit !== $currentUser->unit) {
                    abort(403, 'Anda tidak memiliki akses ke unit ini.');
                }
            }
            $data['user_id'] = $request->user_id;
            $data['status'] = $currentUser->isYayasan() ? 'Disetujui' : 'Pending';
        } else {
            $data['user_id'] = Auth::id();
        }

        if ($request->hasFile('lampiran')) {
            $path = $request->file('lampiran')->store('public/lampiran_izin');
            $data['lampiran'] = $path;
        }

        $izin = Izin::create($data);
        
        // Auto-sync jika admin yang buat (karena status langsung Disetujui)
        if ($izin->status === 'Disetujui') {
            $this->syncToAbsensi($izin);
        }

        // Notif ke Yayasan & Admin Unit (Khusus pengajuan mandiri Pegawai)
        if (!($currentUser->isYayasan() || $currentUser->isAdminUnit())) {
            $namaPegawai = $currentUser->name;
            $unitPegawai = $currentUser->unit;
            $jenisIzin = strtoupper($izin->jenis_izin);
            $tglIzin = $izin->tgl_mulai->format('d/m/Y');
            $alasanIzin = $izin->alasan;

            $msg = "🔔 *PENGAJUAN IZIN/SAKIT BARU*\n\n"
                 . "Terdapat permohonan baru yang memerlukan validasi:\n\n"
                 . "👤 Pegawai: *{$namaPegawai}*\n"
                 . "🏢 Unit: *{$unitPegawai}*\n"
                 . "📂 Jenis: *{$jenisIzin}*\n"
                 . "📅 Tanggal: {$tglIzin}\n"
                 . "💬 Alasan: _{$alasanIzin}_\n\n"
                 . "Mohon segera login ke SIAP Digital untuk melakukan pengecekan.\n"
                 . "Link: " . config('app.url') . "/izin";

            // 1. Kirim ke Yayasan
            $yayasanNumbers = [];
            $envYayasanWa = env('YAYASAN_WA');
            if ($envYayasanWa) {
                $yayasanNumbers[] = $envYayasanWa;
            } else {
                $yayasans = User::where('role', 'yayasan')->get();
                foreach ($yayasans as $y) {
                    if ($y->biodata && $y->biodata->no_wa) {
                        $yayasanNumbers[] = $y->biodata->no_wa;
                    }
                }
            }
            $yayasanNumbers = array_unique($yayasanNumbers);

            if (!empty($yayasanNumbers)) {
                $this->wa->sendMessage(implode(',', $yayasanNumbers), $msg, '5-10');
            }

            // 2. Kirim ke Admin Unit terkait (Dynamic Database Match)
            $adminUnit = null;
            $adminUnits = User::where('role', 'admin_unit')->get();
            foreach ($adminUnits as $au) {
                if ($au->unit && (stripos($unitPegawai, $au->unit) !== false || stripos($au->unit, $unitPegawai) !== false)) {
                    $adminUnit = $au;
                    break;
                }
            }

            if ($adminUnit && $adminUnit->biodata && $adminUnit->biodata->no_wa) {
                $this->wa->sendMessage($adminUnit->biodata->no_wa, $msg);
            }
        }

        return redirect()->back()->with('success', 'Permohonan izin berhasil diproses.');
    }

    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isYayasan() && !$user->isAdminUnit()) {
            abort(403, 'Hanya akun Yayasan atau Admin Unit yang memiliki otoritas validasi izin.');
        }

        $izin = Izin::with('user.biodata')->findOrFail($id);

        // Security check for Admin Unit
        if ($user->isAdminUnit() && $izin->user->unit !== $user->unit) {
            abort(403, 'Anda tidak memiliki wewenang untuk menyetujui izin pegawai dari unit lain.');
        }
        $izin->update([
            'status' => $request->status,
            'keterangan_admin' => $request->keterangan_admin
        ]);

        // Sync ke tabel absensi agar muncul di laporan
        if ($izin->status === 'Disetujui') {
            $this->syncToAbsensi($izin);
        }

        // Notif ke Karyawan
        if ($izin->status == 'Disetujui' && $izin->user->biodata && $izin->user->biodata->no_wa) {
            $dayOfWeek = $izin->tgl_mulai->dayOfWeek;
            $kewajiban = $izin->user->getKewajibanMenit($dayOfWeek);
            $hariTanggal = $izin->tgl_mulai->locale('id')->translatedFormat('l, d F Y');
            $pin = $izin->user->pin_fingerspot ?? '-';
            
            $statusTxt = 'Izin';
            if (strtoupper($izin->jenis_izin) === 'SAKIT') $statusTxt = 'Sakit';
            if (strtoupper($izin->jenis_izin) === 'LIBUR') $statusTxt = 'Libur';
            $namaId = strtoupper($izin->user->name) . " ({$pin})";

            $msg = "[PMU Bungah]\n"
                 . "{$hariTanggal}\n"
                 . "{$namaId}\n"
                 . "Hari ini Anda mempunyai kewajiban {$kewajiban} menit.\n"
                 . "Status kehadiran Anda hari ini: {$statusTxt}\n\n"
                 . "Terima kasih.";
            
            $this->wa->sendMessage($izin->user->biodata->no_wa, $msg);
        }

        return redirect()->back()->with('success', 'Status permohonan izin berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $izin = Izin::findOrFail($id);
        if ($izin->user_id != Auth::id() && !Auth::user()->isYayasan()) {
            abort(403);
        }

        // Hapus data absensi terkait jika izin dihapus
        $start = Carbon::parse($izin->tgl_mulai);
        $end = $izin->tgl_selesai ? Carbon::parse($izin->tgl_selesai) : $start->copy();
        
        Absensi::where('user_id', $izin->user_id)
            ->whereBetween('tgl', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status_kehadiran', ['Izin', 'Sakit'])
            ->delete();

        if ($izin->lampiran) {
            Storage::delete($izin->lampiran);
        }

        $izin->delete();
        return redirect()->back()->with('success', 'Permohonan izin berhasil dihapus.');
    }
}

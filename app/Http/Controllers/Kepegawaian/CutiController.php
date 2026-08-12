<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cuti;
use Illuminate\Support\Facades\Auth;

class CutiController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->isYayasan()) {
            $query = Cuti::with('user');
        } elseif ($user->isAdminUnit()) {
            $query = Cuti::with('user')
                ->where(function($q) use ($user) {
                    $q->where('unit', $user->unit)
                      ->orWhereHas('user', function($qu) use ($user) {
                          $qu->where('unit', $user->unit);
                      });
                });
        } else {
            $query = Cuti::where('user_id', $user->id);
        }

        // Apply status filter
        if (request('status')) {
            $query->where('status_akhir', request('status'));
        }

        // Apply search filter
        if (request('search')) {
            $query->where(function($q) {
                $q->where('alasan', 'like', '%' . request('search') . '%')
                  ->orWhereHas('user', function($qu) {
                      $qu->where('name', 'like', '%' . request('search') . '%');
                  });
            });
        }

        $cutis = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return view('kepegawaian.cuti', compact('cutis'));
    }

    public function create()
    {
        $user = Auth::user();
        $biodata = $user->biodata;
        return view('kepegawaian.cuti_tambah', compact('user', 'biodata'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'unit' => 'required|string',
            'alasan' => 'required|string',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'dokumen' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'ket_dokumen' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        $docPath = null;
        if ($request->hasFile('dokumen')) {
            $docPath = $request->file('dokumen')->store('dokumen_cuti', 'public');
        }

        $cuti = Cuti::create([
            'user_id' => Auth::id(),
            'unit' => $request->unit,
            'alasan' => $request->alasan,
            'tgl_mulai' => $request->tgl_mulai,
            'tgl_selesai' => $request->tgl_selesai,
            'dokumen_pendukung' => $docPath,
            'ket_dokumen' => $request->ket_dokumen,
            'catatan' => $request->catatan,
            'status_unit' => 'Pending',
            'status_yayasan' => 'Pending',
            'status_akhir' => 'Pending',
        ]);

        // [LOGIC WA] Kirim Notif ke Yayasan & Admin Unit
        try {
            $namaPegawai = Auth::user()->name;
            $unitPegawai = $request->unit;
            $tglMulai = \Carbon\Carbon::parse($request->tgl_mulai)->format('d/m/Y');
            $tglSelesai = \Carbon\Carbon::parse($request->tgl_selesai)->format('d/m/Y');
            
            $msg = "📢 *PENGAJUAN CUTI BARU*\n\n"
                 . "Terdapat permohonan cuti baru yang memerlukan validasi:\n\n"
                 . "👤 Nama: *{$namaPegawai}*\n"
                 . "🏢 Unit: *{$unitPegawai}*\n"
                 . "📅 Tanggal: *{$tglMulai} s/d {$tglSelesai}*\n"
                 . "📝 Alasan: {$request->alasan}\n\n"
                 . "Mohon segera login ke SIAP Digital untuk melakukan pengecekan.\n"
                 . "Link: " . config('app.url') . "/kepegawaian/cuti\n\n"
                 . "_SIAP DIGITAL PMU Bungah_";

            $fonnte = new \App\Services\FonnteService();

            // 1. Kirim ke Yayasan
            $yayasanNumbers = [];
            $envYayasanWa = env('YAYASAN_WA');
            if ($envYayasanWa) {
                $yayasanNumbers[] = $envYayasanWa;
            } else {
                $yayasans = \App\Models\User::where('role', 'yayasan')->get();
                foreach ($yayasans as $y) {
                    if ($y->biodata && $y->biodata->no_wa) {
                        $yayasanNumbers[] = $y->biodata->no_wa;
                    }
                }
            }
            $yayasanNumbers = array_unique($yayasanNumbers);

            if (!empty($yayasanNumbers)) {
                $fonnte->sendMessage(implode(',', $yayasanNumbers), $msg, '5-10');
            }

            // 2. Kirim ke Admin Unit terkait (Dynamic Database Match)
            $adminUnit = null;
            $adminUnits = \App\Models\User::where('role', 'admin_unit')->get();
            foreach ($adminUnits as $au) {
                if ($au->unit && (stripos($unitPegawai, $au->unit) !== false || stripos($au->unit, $unitPegawai) !== false)) {
                    $adminUnit = $au;
                    break;
                }
            }

            if ($adminUnit && $adminUnit->biodata && $adminUnit->biodata->no_wa) {
                $fonnte->sendMessage($adminUnit->biodata->no_wa, $msg);
            }
        } catch (\Exception $e) {
            // Silently fail WA
        }

        return redirect()->route('kepegawaian.cuti')->with('success', 'Pengajuan cuti berhasil dikirim.');
    }

    public function updateStatus(Request $request, $id)
    {
        $cuti = Cuti::findOrFail($id);
        $user = Auth::user();

        if ($user->isAdminUnit()) { 
            $cuti->update(['status_unit' => $request->status]);
        } elseif ($user->isYayasan()) {
            // Jika Yayasan setuju, dan Unit masih pending, anggap Unit juga setuju (Bypass)
            if ($request->status == 'Disetujui' && $cuti->status_unit == 'Pending') {
                $cuti->update([
                    'status_unit' => 'Disetujui',
                    'status_yayasan' => 'Disetujui'
                ]);
            } else {
                $cuti->update(['status_yayasan' => $request->status]);
            }
        }

        // Sinkronisasi Status Akhir
        $cuti->refresh();
        if ($cuti->status_unit == 'Disetujui' && $cuti->status_yayasan == 'Disetujui') {
            $cuti->update(['status_akhir' => 'Disetujui']);
            $this->syncToAbsensi($cuti);
        } elseif ($cuti->status_unit == 'Ditolak' || $cuti->status_yayasan == 'Ditolak') {
            $cuti->update(['status_akhir' => 'Ditolak']);
        }

        // [LOGIC WA] Notifikasi Berjenjang
        try {
            $fonnte = new \App\Services\FonnteService();
            $namaPegawai = $cuti->user->name;
            $tglMulai = \Carbon\Carbon::parse($cuti->tgl_mulai)->format('d/m/Y');
            $tglSelesai = \Carbon\Carbon::parse($cuti->tgl_selesai)->format('d/m/Y');

            // 1. Jika Admin Unit setuju, Kirim Notif ke Yayasan
            if ($user->isAdminUnit() && $request->status == 'Disetujui') {
                $yayasanNumbers = [];
                $envYayasanWa = env('YAYASAN_WA');
                if ($envYayasanWa) {
                    $yayasanNumbers[] = $envYayasanWa;
                } else {
                    $yayasans = \App\Models\User::where('role', 'yayasan')->get();
                    foreach ($yayasans as $y) {
                        if ($y->biodata && $y->biodata->no_wa) {
                            $yayasanNumbers[] = $y->biodata->no_wa;
                        }
                    }
                }
                $yayasanNumbers = array_unique($yayasanNumbers);

                $waMessage = "📢 *VALIDASI CUTI (YAYASAN)*\n\n"
                           . "Admin Unit telah menyetujui pengajuan cuti berikut. Mohon untuk melakukan validasi akhir:\n\n"
                           . "👤 Nama: *{$namaPegawai}*\n"
                           . "🏢 Unit: *{$cuti->unit}*\n"
                           . "📅 Tanggal: *{$tglMulai} s/d {$tglSelesai}*\n"
                           . "📝 Alasan: {$cuti->alasan}\n\n"
                           . "Link: " . config('app.url') . "/kepegawaian/cuti";

                if (!empty($yayasanNumbers)) {
                    $fonnte->sendMessage(implode(',', $yayasanNumbers), $waMessage, '5-10');
                }
            }

            // 2. Notif ke Pegawai jika status sudah FINAL (Disetujui/Ditolak)
            if ($cuti->status_akhir !== 'Pending' && $cuti->user->biodata && $cuti->user->biodata->no_wa) {
                $statusTxt = $cuti->status_akhir == 'Disetujui' ? "✅ *DISETUJUI*" : "❌ *DITOLAK*";
                $waMessage = "[SIAP DIGITAL - PMU Bungah]\n\n"
                           . "Halo *{$namaPegawai}*,\n"
                           . "Pengajuan cuti Anda pada tanggal *{$tglMulai} s/d {$tglSelesai}* telah selesai diproses.\n\n"
                           . "Status Akhir: {$statusTxt}\n"
                           . "Terima kasih.";
                $fonnte->sendMessage($cuti->user->biodata->no_wa, $waMessage);
            }
        } catch (\Exception $e) {
            // Silently fail WA
        }

        return redirect()->back()->with('success', 'Status cuti berhasil diperbarui (Sinkron dengan Unit).');
    }

    public function destroy($id)
    {
        $cuti = Cuti::findOrFail($id);
        
        if (Auth::id() !== $cuti->user_id && !Auth::user()->isYayasan() && !Auth::user()->isAdminUnit()) {
            abort(403);
        }

        // Hapus data absensi terkait jika cuti dihapus
        $start = \Carbon\Carbon::parse($cuti->tgl_mulai);
        $end = $cuti->tgl_selesai ? \Carbon\Carbon::parse($cuti->tgl_selesai) : $start->copy();
        
        \App\Models\Absensi::where('user_id', $cuti->user_id)
            ->whereBetween('tgl', [$start->toDateString(), $end->toDateString()])
            ->where('status_kehadiran', 'Cuti')
            ->delete();

        if ($cuti->dokumen_pendukung) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($cuti->dokumen_pendukung);
        }

        $cuti->delete();

        return redirect()->back()->with('success', 'Data pengajuan cuti berhasil dihapus.');
    }

    /**
     * Sinkronisasi data cuti ke tabel absensi setelah disetujui secara final.
     */
    protected function syncToAbsensi(Cuti $cuti)
    {
        if ($cuti->status_akhir !== 'Disetujui') return;

        $start = \Carbon\Carbon::parse($cuti->tgl_mulai);
        $end = $cuti->tgl_selesai ? \Carbon\Carbon::parse($cuti->tgl_selesai) : $start->copy();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $tgl = $date->toDateString();
            
            \App\Models\Absensi::updateOrCreate(
                ['user_id' => $cuti->user_id, 'tgl' => $tgl],
                [
                    'status_kehadiran' => 'Cuti',
                    'keterangan' => $cuti->alasan . " (Cuti)",
                    'pin' => $cuti->user->pin_fingerspot ?? 'CUTI_SYSTEM',
                ]
            );
        }
    }
}

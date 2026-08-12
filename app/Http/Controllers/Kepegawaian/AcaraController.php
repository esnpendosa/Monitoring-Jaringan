<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Acara;
use App\Models\AbsensiAcara;
use App\Models\User;
use App\Services\FingerprintService;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AcaraController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $acaras = Acara::with(['absensiAcaras' => function($q) use ($user) {
            $q->where('user_id', $user->id);
        }])->orderBy('tanggal', 'desc')->paginate(10);
        
        return view('kepegawaian.acara.index', compact('acaras'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->isYayasan() && !Auth::user()->isAdminUnit()) {
            return redirect()->back()->with('error', 'Hanya Admin atau Yayasan yang bisa menambah acara.');
        }

        $request->validate([
            'nama' => 'required',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'lokasi' => 'nullable',
        ]);

        Acara::create([
            'nama' => $request->nama,
            'tanggal' => $request->tanggal,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'lokasi' => $request->lokasi,
            'qr_code' => 'ACARA-' . strtoupper(Str::random(10)),
        ]);

        return redirect()->back()->with('success', 'Acara berhasil ditambahkan.');
    }

    public function show(Request $request, $id)
    {
        $acara = Acara::with('absensiAcaras.user')->findOrFail($id);

        // Semua unit dari database pengaturan unit
        $units = \App\Models\Unit::orderBy('nama')->get();

        // Ambil semua pegawai (dengan filter unit jika ada)
        $unitFilter = $request->get('unit');
        $pegawaiQuery = User::where('role', 'pegawai');
        if ($unitFilter && $unitFilter !== 'SEMUA UNIT') {
            $pegawaiQuery->where('unit', $unitFilter);
        }
        // Admin unit hanya bisa lihat unitnya sendiri
        if (Auth::user()->isAdminUnit()) {
            $pegawaiQuery->where('unit', Auth::user()->unit);
        }
        $allPegawai = $pegawaiQuery->orderBy('unit')->orderBy('name')->get();

        // Map kehadiran: user_id => AbsensiAcara
        $hadirsMap = $acara->absensiAcaras->keyBy('user_id');

        $totalHadir = $hadirsMap->count();

        return view('kepegawaian.acara.show', compact('acara', 'units', 'allPegawai', 'hadirsMap', 'totalHadir', 'unitFilter'));
    }

    public function destroy(string $id)
    {
        if (!Auth::user()->isYayasan() && !Auth::user()->isAdminUnit()) {
            return redirect()->back()->with('error', 'Hanya Admin atau Yayasan yang bisa menghapus acara.');
        }

        $acara = Acara::findOrFail($id);
        $acara->delete();
        return redirect()->back()->with('success', 'Acara berhasil dihapus.');
    }

    public function scanPage($qr_code)
    {
        $acara = Acara::where('qr_code', $qr_code)->firstOrFail();
        return view('kepegawaian.acara.scan', compact('acara'));
    }

    public function doScan(Request $request, FingerprintService $fingerprintService)
    {
        $request->validate([
            'qr_code' => 'required',
            'user_id' => 'required|exists:users,id',
        ]);

        $acara = Acara::where('qr_code', $request->qr_code)->firstOrFail();
        $user = User::findOrFail($request->user_id);

        // Check if already scanned
        $exists = AbsensiAcara::where('acara_id', $acara->id)
            ->where('user_id', $user->id)
            ->first();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda sudah melakukan absensi untuk acara ini.'
            ], 422);
        }

        // Save to AbsensiAcara
        AbsensiAcara::create([
            'acara_id' => $acara->id,
            'user_id' => $user->id,
            'waktu_scan' => now(),
        ]);

        // Synchronize with regular attendance (Fingerprint style)
        // If the event is today, we count this as a scan
        if ($acara->tanggal == date('Y-m-d')) {
            $fingerprintService->syncLog([
                'date_time' => now()->toDateTimeString(),
                'status' => 0, // Assume 0 for check-in style sync
            ], 'QR: ' . $acara->nama, $user->id);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi berhasil! ' . $user->name . ' telah terdaftar.'
        ]);
    }

    public function rekapPerUnit(Request $request, $id)
    {
        if (!Auth::user()->isYayasan() && !Auth::user()->isAdminUnit()) {
            abort(403, 'Hanya Admin atau Yayasan yang bisa mengunduh rekap.');
        }

        $acara = Acara::with(['absensiAcaras.user'])->findOrFail($id);
        $unitFilter = $request->get('unit');

        // Ambil semua pegawai (filter berdasarkan unit jika ada)
        $pegawaiQuery = User::where('role', 'pegawai');
        if ($unitFilter && $unitFilter !== 'SEMUA UNIT') {
            $pegawaiQuery->where('unit', $unitFilter);
        }
        // Admin unit hanya bisa lihat unit sendiri
        if (Auth::user()->isAdminUnit()) {
            $pegawaiQuery->where('unit', Auth::user()->unit);
        }
        $allPegawai = $pegawaiQuery->orderBy('unit')->orderBy('name')->get();

        // Map kehadiran per user
        $hadirsMap = $acara->absensiAcaras->keyBy('user_id');

        $unitLabel = ($unitFilter && $unitFilter !== 'SEMUA UNIT') ? $unitFilter : 'Semua_Unit';
        $namaAcara = str_replace([' ', '/'], ['_', '-'], $acara->nama);
        $fileName = "Rekap_Absen_{$namaAcara}_{$unitLabel}.csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $columns = ['No', 'Nama Pegawai', 'Unit', 'Status Kehadiran', 'Waktu Scan'];

        $callback = function() use ($allPegawai, $hadirsMap, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $no = 1;
            foreach ($allPegawai as $pegawai) {
                $absen = $hadirsMap->get($pegawai->id);
                fputcsv($file, [
                    $no++,
                    $pegawai->name,
                    $pegawai->unit ?? '-',
                    $absen ? 'HADIR' : 'TIDAK HADIR',
                    $absen ? \Carbon\Carbon::parse($absen->waktu_scan)->format('d/m/Y H:i:s') : '-',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

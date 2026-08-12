<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Absensi;
use Illuminate\Support\Facades\Auth;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Self-healing: Sync any approved Cuti records that are missing in the absensis table
        try {
            $approvedCutis = \App\Models\Cuti::where('status_akhir', 'Disetujui')->with('user')->get();
            foreach ($approvedCutis as $c) {
                if (!$c->user) continue;
                $start = \Carbon\Carbon::parse($c->tgl_mulai);
                $end = $c->tgl_selesai ? \Carbon\Carbon::parse($c->tgl_selesai) : $start->copy();
                
                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    $tgl = $date->toDateString();
                    
                    $exists = \App\Models\Absensi::where('user_id', $c->user_id)
                        ->where('tgl', $tgl)
                        ->where('status_kehadiran', 'Cuti')
                        ->exists();
                        
                    if (!$exists) {
                        $currentAbs = \App\Models\Absensi::where('user_id', $c->user_id)->where('tgl', $tgl)->first();
                        if (!$currentAbs || (!$currentAbs->jam_masuk && $currentAbs->status_kehadiran !== 'Cuti')) {
                            \App\Models\Absensi::updateOrCreate(
                                ['user_id' => $c->user_id, 'tgl' => $tgl],
                                [
                                    'status_kehadiran' => 'Cuti',
                                    'keterangan' => $c->alasan . " (Cuti)",
                                    'pin' => $c->user->pin_fingerspot ?? 'CUTI_SYSTEM',
                                ]
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently ignore
        }

        // Self-healing: Sync any approved Izin records that are missing in the absensis table
        try {
            $approvedIzins = \App\Models\Izin::where('status', 'Disetujui')->with('user')->get();
            foreach ($approvedIzins as $i) {
                if (!$i->user) continue;
                $start = \Carbon\Carbon::parse($i->tgl_mulai);
                $end = $i->tgl_selesai ? \Carbon\Carbon::parse($i->tgl_selesai) : $start->copy();
                
                $jenis = strtoupper($i->jenis_izin);
                $statusAbsensi = 'Izin';
                if ($jenis === 'SAKIT') $statusAbsensi = 'Sakit';
                if ($jenis === 'LIBUR') $statusAbsensi = 'Libur';
                if ($jenis === 'CUTI') $statusAbsensi = 'Cuti';

                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    $tgl = $date->toDateString();
                    
                    $exists = \App\Models\Absensi::where('user_id', $i->user_id)
                        ->where('tgl', $tgl)
                        ->where('status_kehadiran', $statusAbsensi)
                        ->exists();
                        
                    if (!$exists) {
                        $currentAbs = \App\Models\Absensi::where('user_id', $i->user_id)->where('tgl', $tgl)->first();
                        if (!$currentAbs || (!$currentAbs->jam_masuk && $currentAbs->status_kehadiran !== $statusAbsensi)) {
                            \App\Models\Absensi::updateOrCreate(
                                ['user_id' => $i->user_id, 'tgl' => $tgl],
                                [
                                    'status_kehadiran' => $statusAbsensi,
                                    'keterangan' => $i->alasan . " (" . $i->jenis_izin . ")",
                                    'pin' => $i->user->pin_fingerspot ?? 'IZIN_SYSTEM',
                                ]
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently ignore
        }
        
        // Cek target user. Jika pegawai, tampilkan miliknya. Jika admin/yayasan, bisa pilih user or 'all'.
        $targetUserId = $request->get('user_id');
        if (!$targetUserId) {
            $targetUserId = ($user->isYayasan() || $user->isAdminUnit()) ? 'all' : $user->id;
        }

        // Security check
        if ($targetUserId !== 'all' && $targetUserId != $user->id && !($user->isYayasan() || $user->isAdminUnit())) {
            abort(403);
        }
        $month = $request->filled('month') ? (int) $request->month : (int) date('n');
        $year = $request->filled('year') ? (int) $request->year : (int) date('Y');
        
        // Default tab: collective view usually wants monthly summary, individual usually wants daily logs
        $defaultTab = ($targetUserId === 'all') ? 'bulanan' : 'harian';
        $tab = $request->get('tab', $defaultTab);

        // Untuk filter admin
        $allUsers = [];
        if ($user->isYayasan() || $user->isAdminUnit()) {
            $allUsers = \App\Models\User::with(['schedules'])->where('role', 'pegawai')
                ->when($user->isAdminUnit(), function($q) use ($user) {
                    return $q->where('unit', $user->unit);
                })
                ->orderBy('name')
                ->get()
                ->unique('id');
        }

        $counts = [
            'hadir' => 0, 'alpha' => 0, 'izin' => 0, 'sakit' => 0, 'cuti' => 0, 'total' => 0, 'persentase' => 0
        ];

        // GLOBAL REPORT (All Employees)
        if ($targetUserId === 'all') {
            if ($tab === 'harian') {
                $perPage = $request->get('per_page', 50);
                $query = Absensi::with('user')
                    ->whereMonth('tgl', $month)
                    ->whereYear('tgl', $year)
                    ->when($request->get('unit'), function($q) use ($request) {
                        return $q->whereHas('user', fn($u) => $u->where('unit', $request->get('unit')));
                    });
                
                $reportData = $query->orderBy('tgl', 'desc')->paginate($perPage);
                
                $counts = [
                    'hadir' => (clone $query)->whereIn('status_kehadiran', ['Hadir', 'Terlambat', 'Pulang Lebih Awal', 'Terlambat & Pulang Awal'])->count(),
                    'alpha' => 0,
                    'izin' => (clone $query)->where('status_kehadiran', 'Izin')->count(),
                    'sakit' => (clone $query)->where('status_kehadiran', 'Sakit')->count(),
                    'cuti' => (clone $query)->where('status_kehadiran', 'Cuti')->count(),
                ];
                $counts['total'] = $counts['hadir'] + $counts['izin'] + $counts['sakit'] + $counts['cuti'];
                $counts['persentase'] = 0;

                $targetUser = (object)['id' => 'all', 'name' => 'SEMUA PEGAWAI', 'unit' => ($request->get('unit') ?: ($user->isAdminUnit() ? $user->unit : 'SEMUA UNIT')), 'kode_pegawai' => '-'];
                $units = \App\Models\Unit::orderBy('nama', 'asc')->pluck('nama');
                
                return view('kepegawaian.absensi_report', compact('tab', 'reportData', 'month', 'year', 'targetUser', 'counts', 'allUsers', 'targetUserId', 'units'));
            }

            $reportData = [];
            $daysInMonth = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;

            foreach ($allUsers as $u) {
                $uAbsensis = Absensi::where('user_id', $u->id)
                    ->whereMonth('tgl', $month)
                    ->whereYear('tgl', $year)
                    ->get();
                
                $hadir = $uAbsensis->whereIn('status_kehadiran', ['Hadir', 'Terlambat', 'Pulang Lebih Awal', 'Terlambat & Pulang Awal'])->count();
                $izin = $uAbsensis->where('status_kehadiran', 'Izin')->count();
                $sakit = $uAbsensis->where('status_kehadiran', 'Sakit')->count();
                $cuti = $uAbsensis->where('status_kehadiran', 'Cuti')->count();
                
                $totalKewajiban = 0;
                $totalKehadiran = 0;
                $workDays = 0;

                $uAbsensisKeyed = $uAbsensis->keyBy(function($item) {
                    return \Carbon\Carbon::parse($item->tgl)->format('Y-m-d');
                });
                
                $maxDay = ($month == date('n') && $year == date('Y')) ? date('j') : $daysInMonth;
                for ($d = 1; $d <= $maxDay; $d++) {
                    $carbonDate = \Carbon\Carbon::create($year, $month, $d);
                    $dateString = $carbonDate->format('Y-m-d');
                    $dayOfWeek = $carbonDate->dayOfWeek;
                    $kewajiban = $u->getKewajibanMenit($dayOfWeek);
                    
                    if ($kewajiban > 0) {
                        $workDays++;
                        $abs = $uAbsensisKeyed->get($dateString);
                        $status = $abs ? $abs->status_kehadiran : 'Alpha';

                        if (!in_array($status, ['Izin', 'Sakit', 'Cuti', 'Libur'])) {
                            $totalKewajiban += $kewajiban;
                            if ($abs && $abs->jam_masuk && $abs->jam_pulang) {
                                $start = \Carbon\Carbon::parse($abs->jam_masuk);
                                $end = \Carbon\Carbon::parse($abs->jam_pulang);
                                $kehadiran = $start->diffInMinutes($end);
                                $totalKehadiran += min($kehadiran, $kewajiban);
                            }
                        }
                    }
                }
                
                $alpha = max(0, $workDays - ($hadir + $izin + $sakit + $cuti));

                $reportData[] = [
                    'user' => $u,
                    'hadir' => $hadir,
                    'izin' => $izin,
                    'sakit' => $sakit,
                    'cuti' => $cuti,
                    'alpha' => $alpha,
                    'persentase' => $totalKewajiban > 0 ? round(($totalKehadiran / $totalKewajiban) * 100) : 0
                ];

                // Global totals
                $counts['hadir'] += $hadir;
                $counts['izin'] += $izin;
                $counts['sakit'] += $sakit;
                $counts['cuti'] += $cuti;
                $counts['alpha'] += $alpha;
            }
            $counts['total'] = $counts['hadir'] + $counts['alpha'] + $counts['izin'] + $counts['sakit'] + $counts['cuti'];
            $counts['persentase'] = count($reportData) > 0 ? round(array_sum(array_column($reportData, 'persentase')) / count($reportData)) : 0;

            $targetUser = (object)['id' => 'all', 'name' => 'SEMUA PEGAWAI', 'unit' => ($request->get('unit') ?: ($user->isAdminUnit() ? $user->unit : 'SEMUA UNIT')), 'kode_pegawai' => '-'];
            $units = \App\Models\Unit::orderBy('nama', 'asc')->pluck('nama');
            
            return view('kepegawaian.absensi_report', compact('tab', 'reportData', 'month', 'year', 'targetUser', 'counts', 'allUsers', 'targetUserId', 'units'));
        }

        // SINGLE USER REPORT
        $targetUser = \App\Models\User::with(['biodata', 'schedules'])->findOrFail($targetUserId);
        $units = \App\Models\Unit::orderBy('nama', 'asc')->pluck('nama');
        
        if ($tab === 'harian') {
            $daysInMonth = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;
            $harianData = [];
            
            $devicesJson = \App\Models\Setting::get('fingerspot_devices');
            $devices = $devicesJson ? json_decode($devicesJson, true) : [];
            $defaultLocation = $devices[0]['name'] ?? 'X100-C PUSAT';

            $absensis = Absensi::where('user_id', $targetUserId)
                ->whereMonth('tgl', $month)
                ->whereYear('tgl', $year)
                ->get()
                ->keyBy(function($item) {
                    return \Carbon\Carbon::parse($item->tgl)->format('Y-m-d');
                });

            $totalKewajiban = 0;
            $totalKehadiran = 0;

            $maxDay = ($month == date('n') && $year == date('Y')) ? date('j') : $daysInMonth;
            for ($d = 1; $d <= $maxDay; $d++) {
                $carbonDate = \Carbon\Carbon::create($year, $month, $d);
                $dateString = $carbonDate->format('Y-m-d');
                $dayOfWeek = $carbonDate->dayOfWeek;
                $abs = $absensis->get($dateString);
                $kewajiban = $targetUser->getKewajibanMenit($dayOfWeek);
                $kehadiran = 0;
                
                if ($abs && $abs->jam_masuk && $abs->jam_pulang) {
                    $start = \Carbon\Carbon::parse($abs->jam_masuk);
                    $end = \Carbon\Carbon::parse($abs->jam_pulang);
                    $kehadiran = $start->diffInMinutes($end);
                }

                $status = $abs ? $abs->status_kehadiran : ($kewajiban > 0 ? 'Alpha' : 'Libur');

                // Hanya tampilkan jika hari kerja (kewajiban > 0) atau jika ada data absensi nyata (meskipun libur tapi masuk)
                if ($kewajiban > 0 || ($abs && $abs->jam_masuk)) {
                    $harianData[] = [
                        'tanggal' => $dateString,
                        'kewajiban' => $kewajiban,
                        'kehadiran' => $kehadiran,
                        'status' => $status,
                        'absensi_id' => $abs ? $abs->id : null,
                        'jam_masuk' => $abs ? $abs->jam_masuk : null,
                        'jam_pulang' => $abs ? $abs->jam_pulang : null,
                        'lokasi' => $abs ? ($abs->lokasi ?? $defaultLocation) : $defaultLocation,
                    ];
                }

                // Kalkulasi Summary
                if ($kewajiban > 0) {
                    // Izin, Sakit, Cuti, Libur "tidak dihitung" dalam persentase (mengurangi penyebut)
                    if (!in_array($status, ['Izin', 'Sakit', 'Cuti', 'Libur'])) {
                        $totalKewajiban += $kewajiban;
                        $totalKehadiran += min($kehadiran, $kewajiban);
                    }
                }
            }

            $counts = [
                'hadir' => $absensis->whereIn('status_kehadiran', ['Hadir', 'Terlambat', 'Pulang Lebih Awal', 'Terlambat & Pulang Awal'])->count(),
                'alpha' => count(array_filter($harianData, fn($x) => $x['status'] == 'Alpha')),
                'izin' => $absensis->where('status_kehadiran', 'Izin')->count(),
                'sakit' => $absensis->where('status_kehadiran', 'Sakit')->count(),
                'cuti' => $absensis->where('status_kehadiran', 'Cuti')->count(),
            ];
            $counts['total'] = $counts['hadir'] + $counts['alpha'] + $counts['izin'] + $counts['sakit'] + $counts['cuti'];
            $counts['persentase'] = $totalKewajiban > 0 ? round(($totalKehadiran / $totalKewajiban) * 100) : 0;

            return view('kepegawaian.absensi_report', compact('tab', 'harianData', 'month', 'year', 'targetUser', 'counts', 'allUsers', 'targetUserId', 'units'));

        } else {
            // Bulanan Logic
            $bulananData = [];
            
            for ($m = 1; $m <= 12; $m++) {
                $monthAbs = Absensi::where('user_id', $targetUserId)
                    ->whereMonth('tgl', $m)
                    ->whereYear('tgl', $year)
                    ->get();
                
                if ($monthAbs->count() > 0 || $m <= ($year == date('Y') ? date('n') : 12)) {
                    $hadir = $monthAbs->whereIn('status_kehadiran', ['Hadir', 'Terlambat', 'Pulang Lebih Awal', 'Terlambat & Pulang Awal'])->count();
                    $izin = $monthAbs->where('status_kehadiran', 'Izin')->count();
                    $sakit = $monthAbs->where('status_kehadiran', 'Sakit')->count();
                    $cuti = $monthAbs->where('status_kehadiran', 'Cuti')->count();
                    
                    $mTotalKewajiban = 0;
                    $mTotalKehadiran = 0;
                    $mAlpha = 0;
                    $mWorkDays = 0;
                    
                    $monthAbsKeyed = $monthAbs->keyBy(function($item) {
                        return \Carbon\Carbon::parse($item->tgl)->format('Y-m-d');
                    });
                    
                    $daysInMonth = \Carbon\Carbon::create($year, $m, 1)->daysInMonth;
                    $maxDay = ($m == date('n') && $year == date('Y')) ? date('j') : $daysInMonth;
                    
                    for ($d = 1; $d <= $maxDay; $d++) {
                        $carbonDate = \Carbon\Carbon::create($year, $m, $d);
                        $dateStr = $carbonDate->format('Y-m-d');
                        $dayOfWeek = $carbonDate->dayOfWeek;
                        $kewajiban = $targetUser->getKewajibanMenit($dayOfWeek);
                        
                        if ($kewajiban > 0) {
                            $mWorkDays++;
                            $abs = $monthAbsKeyed->get($dateStr);
                            $status = $abs ? $abs->status_kehadiran : 'Alpha';
                            if ($status == 'Alpha') $mAlpha++;

                            if (!in_array($status, ['Izin', 'Sakit', 'Cuti', 'Libur'])) {
                                $mTotalKewajiban += $kewajiban;
                                if ($abs && $abs->jam_masuk && $abs->jam_pulang) {
                                    $start = \Carbon\Carbon::parse($abs->jam_masuk);
                                    $end = \Carbon\Carbon::parse($abs->jam_pulang);
                                    $mTotalKehadiran += min($start->diffInMinutes($end), $kewajiban);
                                }
                            }
                        }
                    }

                    $bulananData[] = [
                        'bulan' => \Carbon\Carbon::create()->month($m)->translatedFormat('F'),
                        'hadir' => $hadir,
                        'izin' => $izin,
                        'sakit' => $sakit,
                        'cuti' => $cuti,
                        'alpha' => $mAlpha,
                        'total' => $mWorkDays,
                        'persentase' => $mTotalKewajiban > 0 ? round(($mTotalKehadiran / $mTotalKewajiban) * 100) : 0
                    ];

                    $counts['hadir'] += $hadir; 
                    $counts['izin'] += $izin; 
                    $counts['sakit'] += $sakit; 
                    $counts['cuti'] += $cuti;
                    $counts['alpha'] += $mAlpha;
                }
            }
            $counts['total'] = $counts['hadir'] + $counts['alpha'] + $counts['izin'] + $counts['sakit'] + $counts['cuti'];
            $counts['persentase'] = count($bulananData) > 0 ? round(array_sum(array_column($bulananData, 'persentase')) / count($bulananData)) : 0;

            return view('kepegawaian.absensi_report', compact('tab', 'bulananData', 'month', 'year', 'targetUser', 'counts', 'allUsers', 'targetUserId', 'units'));
        }
    }

    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        if (!($user->isYayasan() || $user->isAdminUnit())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengunduh rekapitulasi.');
        }

        $month = (int) $request->get('month', date('n'));
        $year = (int) $request->get('year', date('Y'));
        $targetUserId = $request->get('user_id');
        $unit = $request->get('unit');
        
        $query = Absensi::with('user')
            ->whereMonth('tgl', $month)
            ->whereYear('tgl', $year);

        if ($targetUserId && $targetUserId !== 'all') {
            $query->where('user_id', $targetUserId);
        }

        if ($unit && $unit !== 'SEMUA UNIT') {
            $query->whereHas('user', function($q) use ($unit) {
                $q->where('unit', $unit);
            });
        }

        $absensis = $query->orderBy('tgl', 'asc')->get();

        $unitName = ($unit && $unit !== 'SEMUA UNIT') ? $unit : 'Semua_Unit';
        $fileName = "Rekap_Absensi_{$unitName}_{$month}_{$year}.csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Nama Pegawai', 'Unit', 'Tanggal', 'Jam Masuk', 'Jam Pulang', 'Status', 'Lokasi', 'Keterangan'];

        $callback = function() use($absensis, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($absensis as $abs) {
                fputcsv($file, [
                    $abs->user->name,
                    $abs->user->unit,
                    $abs->tgl,
                    $abs->jam_masuk ?? '-',
                    $abs->jam_pulang ?? '-',
                    $abs->status_kehadiran,
                    $abs->lokasi ?? '-',
                    $abs->keterangan ?? '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function today()
    {
        $user = Auth::user();
        $today = \Carbon\Carbon::today()->toDateString();
        
        // Cek akses: Yayasan dan Admin Unit bisa melihat semua, Pegawai hanya miliknya
        if ($user->role === 'yayasan' || $user->role === 'admin_unit') {
            $absensis = Absensi::where('tgl', $today)->with('user')->orderBy('created_at', 'desc')->get();
        } else {
            $absensis = Absensi::where('user_id', $user->id)->where('tgl', $today)->get();
        }

        $devicesJson = \App\Models\Setting::get('fingerspot_devices');
        $devices = $devicesJson ? json_decode($devicesJson, true) : [];

        return view('kepegawaian.absensi_today', compact('absensis', 'devices'));
    }

    public function storeManual(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser->isYayasan()) {
            abort(403);
        }

        $request->validate([
            'user_id'          => 'required|exists:users,id',
            'tgl'              => 'required|date',
            'jam_masuk'        => 'nullable|date_format:H:i',
            'jam_pulang'       => 'nullable|date_format:H:i',
            'status_kehadiran' => 'required|string',
            'keterangan'       => 'nullable|string',
        ]);

        $targetUser = \App\Models\User::findOrFail($request->user_id);

        $absensi = \App\Models\Absensi::updateOrCreate(
            ['user_id' => $targetUser->id, 'tgl' => $request->tgl],
            [
                'jam_masuk'        => $request->jam_masuk ?: null,
                'jam_pulang'       => $request->jam_pulang ?: null,
                'status_kehadiran' => $request->status_kehadiran,
                'keterangan'       => $request->keterangan,
                'pin'              => $targetUser->pin_fingerspot ?? 'MANUAL',
            ]
        );

        return redirect()->back()->with('success', "Data absensi {$targetUser->name} berhasil disimpan.");
    }

    public function sync(\App\Services\FingerprintService $fingerprintService)
    {
        if (Auth::user()->role !== 'yayasan') {
            abort(403);
        }

        $result  = $fingerprintService->pullData();
        $total   = $result['total_synced'];
        $devices = $result['devices'];

        // Build per-device summary for flash message
        $lines = [];
        foreach ($devices as $d) {
            if ($d['status'] === 'ok') {
                $lines[] = "✅ {$d['name']}: {$d['synced']} log";
            } else {
                $lines[] = "❌ {$d['name']}: " . ($d['error'] ?? 'Gagal');
            }
        }

        $summary = implode(' | ', $lines);
        $msg     = "Sinkronisasi selesai — {$total} log diproses. {$summary}";

        $allFailed = collect($devices)->every(fn($d) => $d['status'] !== 'ok');

        return redirect()->back()->with(
            $allFailed ? 'error' : 'success',
            $msg
        );
    }

    public function importCsv(Request $request)
    {
        $currentUser = Auth::user();
        if (!($currentUser->isYayasan() || $currentUser->isAdminUnit())) {
            abort(403);
        }

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file   = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        // Skip header row
        $header = fgetcsv($handle);

        $imported = 0;
        $skipped  = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Try to get PIN from first column
            $pin  = trim($row[0] ?? '');
            $date = trim($row[1] ?? '');
            $in   = trim($row[2] ?? '') ?: null;
            $out  = trim($row[3] ?? '') ?: null;

            if (!$pin || !$date) { $skipped++; continue; }

            // Normalize date format
            try {
                $tgl = \Carbon\Carbon::parse($date)->toDateString();
            } catch (\Exception $e) { $skipped++; continue; }

            // Match user by pin_fingerspot
            $user = \App\Models\User::where('pin_fingerspot', $pin)->first();
            if (!$user) { $skipped++; continue; }

            // Auto-determine status
            $status = 'Hadir';
            if ($in && $in > '07:30:00') $status = 'Terlambat';

            \App\Models\Absensi::updateOrCreate(
                ['user_id' => $user->id, 'tgl' => $tgl],
                [
                    'pin'              => $pin,
                    'jam_masuk'        => $in,
                    'jam_pulang'       => $out,
                    'status_kehadiran' => $status,
                    'keterangan'       => 'Import CSV',
                ]
            );
            $imported++;
        }

        fclose($handle);

        return redirect()->back()->with('success', "Import selesai: {$imported} data berhasil diimpor, {$skipped} data dilewati (PIN tidak terdaftar).");
    }

    public function destroy($id)
    {
        if (!(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus data ini.');
        }
        
        $absensi = Absensi::findOrFail($id);
        $absensi->delete();
        return redirect()->back()->with('success', 'Data absensi berhasil dihapus.');
    }

    public function recap()
    {
        $user = Auth::user();
        if (method_exists($user, 'isAdminUnit') && ($user->isAdminUnit() || $user->isYayasan())) {
            $absensis = Absensi::with('user')->orderBy('tgl', 'desc')->get();
        } else {
            $absensis = Absensi::where('user_id', $user->id)->orderBy('tgl', 'desc')->get();
        }
        return view('kepegawaian.absensi_recap', compact('absensis'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use Illuminate\Http\Request;

class TagihanController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $filterKhusus = $request->query('filter_khusus');
        $filterBulan = $request->query('filter_bulan');
        $filterTahun = $request->query('filter_tahun');
        $user = auth()->user();
        
        // OPTIMIZATION: Eager load relationships to prevent N+1 queries
        // Note: select tagihan.* explicitly because of join below
        $query = Tagihan::with(['pelanggan' => function($q) {
            $q->select('id_pelanggan', 'kode_pelanggan', 'nama_pelanggan', 'id_user', 'no_wa', 'wa_active');
        }]);

        if ($filterBulan) {
            $query->where('bulan', $filterBulan);
        }

        if ($filterTahun) {
            $query->where('tahun', $filterTahun);
        }

        if ($search) {
            $query->where(function ($mainQuery) use ($search) {
                $mainQuery->whereHas('pelanggan', function ($q) use ($search) {
                    $q->where('nama_pelanggan', 'like', "%{$search}%")
                      ->orWhere('kode_pelanggan', 'like', "%{$search}%")
                      ->orWhere('no_wa', 'like', "%{$search}%")
                      ->orWhere('mikrotik_username', 'like', "%{$search}%")
                      ->orWhere('ip_address', 'like', "%{$search}%")
                      ->orWhere('alamat', 'like', "%{$search}%");
                });

                $mainQuery->orWhere('status', 'like', "%{$search}%")
                          ->orWhere('metode_pembayaran', 'like', "%{$search}%")
                          ->orWhere('tahun', 'like', "%{$search}%");

                // Mapping month names (both Indonesian and English) to integer values
                $monthMap = [
                    'jan' => 1, 'peb' => 2, 'feb' => 2, 'mar' => 3, 'apr' => 4,
                    'mei' => 5, 'may' => 5, 'jun' => 6, 'jul' => 7, 'agu' => 8,
                    'aug' => 8, 'sep' => 9, 'okt' => 10, 'oct' => 10, 'nov' => 11,
                    'des' => 12, 'dec' => 12,
                    'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
                    'juni' => 6, 'juli' => 7, 'agustus' => 8, 'september' => 9,
                    'oktober' => 10, 'november' => 11, 'desember' => 12
                ];
                $lowerSearch = strtolower($search);
                if (isset($monthMap[$lowerSearch])) {
                    $mainQuery->orWhere('bulan', $monthMap[$lowerSearch]);
                }
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($filterKhusus) {
            if ($filterKhusus === 'unpaid_3_months') {
                $customerIds = Tagihan::where('status', 'unpaid')
                    ->groupBy('id_pelanggan')
                    ->havingRaw('count(*) >= 3')
                    ->pluck('id_pelanggan');
                $query->whereIn('id_pelanggan', $customerIds);
            } elseif ($filterKhusus === 'unpaid_2_months') {
                $customerIds = Tagihan::where('status', 'unpaid')
                    ->groupBy('id_pelanggan')
                    ->havingRaw('count(*) >= 2')
                    ->pluck('id_pelanggan');
                $query->whereIn('id_pelanggan', $customerIds);
            } elseif ($filterKhusus === 'paid_3_months') {
                $paidGroups = Tagihan::select('id_pelanggan', \Illuminate\Support\Facades\DB::raw('DATE(paid_at) as pay_date'))
                    ->where('status', 'paid')
                    ->whereNotNull('paid_at')
                    ->groupBy('id_pelanggan', \Illuminate\Support\Facades\DB::raw('DATE(paid_at)'))
                    ->havingRaw('count(*) >= 3')
                    ->get();
                
                $query->where(function($q) use ($paidGroups) {
                    foreach ($paidGroups as $group) {
                        $q->orWhere(function($sub) use ($group) {
                            $sub->where('id_pelanggan', $group->id_pelanggan)
                                ->whereDate('paid_at', $group->pay_date);
                        });
                    }
                    if ($paidGroups->isEmpty()) {
                        $q->whereNull('id_tagihan');
                    }
                });
            }
        }

        // If user is a customer, only show their own bills
        $roleName = $user->role ? $user->role->name : 'Pelanggan';
        $isPelanggan = ($roleName === 'Pelanggan' || $user->id_role == 4);

        if ($isPelanggan) {
            $query->whereHas('pelanggan', function ($q) use ($user) {
                $q->where('id_user', $user->id);
            });
        }

        // Sort: prefix huruf dulu (A, AB, AC, dst), lalu angka secara numerik (1, 2, 10, 100)
        // Contoh hasil: A1, A2, A10, A100, AB1, AB2, AC1, dst
        $query->join('pelanggan', 'pelanggan.id_pelanggan', '=', 'tagihan.id_pelanggan')
              ->select('tagihan.*')
              ->orderByRaw("
                  REGEXP_REPLACE(pelanggan.kode_pelanggan, '[0-9]', ''),
                  CAST(REGEXP_REPLACE(pelanggan.kode_pelanggan, '[^0-9]', '') AS UNSIGNED),
                  tagihan.tahun,
                  tagihan.bulan
              ");
        
        // OPTIMIZATION: Use pagination to limit records loaded per page
        $tagihan = $query->paginate(50)->appends($request->query());

        // OPTIMIZATION: Only load active customers for modal, with minimal fields
        $allPelanggan = Pelanggan::where('is_active', true)
            ->select('id_pelanggan', 'kode_pelanggan', 'nama_pelanggan', 'harga_layanan')
            ->orderBy('kode_pelanggan')
            ->get();
            
        return view('content.billing.index', compact('tagihan', 'allPelanggan'));
    }

    public function store(Request $request)
    {
        if (!in_array(auth()->user()->id_role, [1, 2])) abort(403);

        $request->validate([
            'id_pelanggan' => 'required|exists:pelanggan,id_pelanggan',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
            'jumlah' => 'required|numeric|min:0',
            'status' => 'required|in:unpaid,paid,pending,cancelled',
            'metode_pembayaran' => 'nullable|string',
            'paid_at' => 'nullable|date',
            'catatan_admin' => 'nullable|string',
        ]);

        $exists = Tagihan::where('id_pelanggan', $request->id_pelanggan)
            ->where('bulan', $request->bulan)
            ->where('tahun', $request->tahun)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Gagal: Tagihan untuk pelanggan tersebut pada periode bulan/tahun ini sudah ada.');
        }

        $data = $request->only(['id_pelanggan', 'bulan', 'tahun', 'jumlah', 'status', 'metode_pembayaran', 'paid_at', 'catatan_admin']);
        $data['bayar_di_awal'] = $request->has('bayar_di_awal');

        if ($data['status'] !== 'paid') {
            $data['paid_at'] = null;
            $data['metode_pembayaran'] = null;
            $data['bayar_di_awal'] = false;
        }

        $tagihan = Tagihan::create($data);

        if ($tagihan->status === 'paid') {
            $pelanggan = $tagihan->pelanggan;
            if ($pelanggan && $pelanggan->id_router) {
                try {
                    $mikrotikService = app(\App\Services\MikrotikService::class);
                    $success = $mikrotikService->setSecretStatus($pelanggan->router, $pelanggan->mikrotik_username ?: $pelanggan->kode_pelanggan, $pelanggan->mikrotik_type, false, $pelanggan->ip_address);
                    if ($success) {
                        $pelanggan->update(['is_active' => true, 'is_isolated' => false]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal sync Mikrotik pada tambah tagihan: ' . $e->getMessage());
                }
            }
        }

        \App\Helpers\ActivityLogger::log('Menambahkan data tagihan manual #' . $tagihan->id_tagihan . ' (' . ($tagihan->pelanggan ? $tagihan->pelanggan->nama_pelanggan : 'Umum') . ') status: ' . $tagihan->status, 'tagihan');

        return back()->with('success', 'Tagihan manual berhasil disimpan.');
    }

    public function updateAmount(Request $request, Tagihan $tagihan)
    {
        if (!in_array(auth()->user()->id_role, [1, 2])) abort(403);

        $request->validate([
            'jumlah' => 'required|numeric|min:0',
        ]);

        $tagihan->update([
            'jumlah' => $request->jumlah,
        ]);

        \App\Helpers\ActivityLogger::log('Mengubah nominal tagihan #' . $tagihan->id_tagihan . ' untuk ' . ($tagihan->pelanggan ? $tagihan->pelanggan->nama_pelanggan : 'Umum') . ' menjadi Rp ' . number_format($request->jumlah, 0, ',', '.'), 'tagihan');

        return back()->with('success', 'Jumlah tagihan berhasil diperbarui.');
    }

    public function update(Request $request, Tagihan $tagihan)
    {
        if (!in_array(auth()->user()->id_role, [1, 2])) abort(403);

        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
            'jumlah' => 'required|numeric|min:0',
            'status' => 'required|in:unpaid,paid,pending,cancelled',
            'created_at' => 'nullable|date',
            'metode_pembayaran' => 'nullable|string',
        ]);

        $oldStatus = $tagihan->status;
        $data = $request->all();
        $data['bayar_di_awal'] = $request->has('bayar_di_awal');
        
        if ($data['status'] !== 'paid') {
            $data['bayar_di_awal'] = false;
            $data['paid_at'] = null;
            $data['metode_pembayaran'] = null;
        }

        $tagihan->update($data);

        if ($oldStatus !== 'paid' && $tagihan->status === 'paid') {
            $tagihan->update(['paid_at' => $tagihan->paid_at ?? now()]);
            
            $pelanggan = $tagihan->pelanggan;
            if ($pelanggan && $pelanggan->id_router) {
                $mikrotikService = app(\App\Services\MikrotikService::class);
                $success = $mikrotikService->setSecretStatus($pelanggan->router, $pelanggan->mikrotik_username ?: $pelanggan->kode_pelanggan, $pelanggan->mikrotik_type, false, $pelanggan->ip_address);
                if ($success) {
                    $pelanggan->update(['is_active' => true, 'is_isolated' => false]);
                }
            }

            // Kirim Nota setelah response (non-blocking)
            if ($pelanggan && $pelanggan->no_wa && $pelanggan->wa_active && \App\Models\Setting::get('wa_billing_notification_enabled', '1') == '1') {
                $tid = $tagihan->id_tagihan;
                app()->terminating(function () use ($tid) {
                    try {
                        $t = \App\Models\Tagihan::find($tid);
                        if ($t) (new \App\Services\WhatsappClient())->sendReceipt($t, true);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Gagal kirim nota WA dari update: ' . $e->getMessage());
                    }
                });
            }
        }

        \App\Helpers\ActivityLogger::log('Mengubah data tagihan #' . $tagihan->id_tagihan . ' (' . ($tagihan->pelanggan ? $tagihan->pelanggan->nama_pelanggan : 'Umum') . ') menjadi status: ' . $tagihan->status, 'tagihan');

        return back()->with('success', 'Detail tagihan berhasil diperbarui.');
    }

    public function destroy(Tagihan $tagihan)
    {
        if (!in_array(auth()->user()->id_role, [1, 2])) abort(403);

        try {
            // Hapus file fisik bukti transfer jika ada
            if ($tagihan->bukti_bayar) {
                $filePath = storage_path('app/public/' . $tagihan->bukti_bayar);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            $tagihan->delete();

            return back()->with('success', 'Tagihan berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus tagihan: ' . $e->getMessage());
        }
    }

    public function deleteAll()
    {
        if (!in_array(auth()->user()->id_role, [1, 2])) abort(403);

        try {
            // Hapus semua file fisik bukti transfer terlebih dahulu
            $tagihans = Tagihan::whereNotNull('bukti_bayar')->get();
            foreach ($tagihans as $t) {
                $filePath = storage_path('app/public/' . $t->bukti_bayar);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            // Hapus semua data secara aman
            Tagihan::query()->delete();

            return back()->with('success', 'Semua tagihan berhasil dikosongkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengosongkan data tagihan: ' . $e->getMessage());
        }
    }

    public function destroyDirect(Tagihan $tagihan)
    {
        if (!in_array(auth()->user()->id_role, [1, 2])) abort(403);

        try {
            // Hapus file fisik bukti transfer jika ada
            if ($tagihan->bukti_bayar) {
                $filePath = storage_path('app/public/' . $tagihan->bukti_bayar);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            $tagihan->delete();

            return back()->with('success', 'Tagihan berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus tagihan: ' . $e->getMessage());
        }
    }

    public function deleteAllDirect()
    {
        if (!in_array(auth()->user()->id_role, [1, 2])) abort(403);

        try {
            // Hapus semua file fisik bukti transfer terlebih dahulu
            $tagihans = Tagihan::whereNotNull('bukti_bayar')->get();
            foreach ($tagihans as $t) {
                $filePath = storage_path('app/public/' . $t->bukti_bayar);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            // Hapus semua data secara aman
            Tagihan::query()->delete();

            return back()->with('success', 'Semua tagihan berhasil dikosongkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengosongkan data tagihan: ' . $e->getMessage());
        }
    }

    public function generateMonthlyBills(Request $request)
    {
        $currentMonth = $request->bulan ?? now()->month;
        $currentYear = $request->tahun ?? now()->year;

        $query = Pelanggan::where('is_active', true);

        if ($request->mode === 'user') {
            if (!$request->id_pelanggan) {
                return back()->with('error', 'Gagal: Pelanggan belum dipilih.');
            }
            $query->where('id_pelanggan', $request->id_pelanggan);
        } elseif ($request->mode === 'range') {
            if ($request->date_start && $request->date_end) {
                $query->whereBetween('billing_date', [$request->date_start, $request->date_end]);
            } else {
                return back()->with('error', 'Gagal: Range tanggal jatuh tempo belum ditentukan.');
            }
        } else {
            // Fallback for safety or legacy requests
            if ($request->id_pelanggan) {
                $query->where('id_pelanggan', $request->id_pelanggan);
            } elseif ($request->date_start && $request->date_end) {
                $query->whereBetween('billing_date', [$request->date_start, $request->date_end]);
            } else {
                return back()->with('error', 'Gagal: Parameter pembuatan tagihan tidak valid.');
            }
        }

        $pelanggans = $query->get();
        $generatedCount = 0;

        $waClient = new \App\Services\WhatsappClient();
        foreach ($pelanggans as $p) {
            // Check if bill already exists for this month
            $exists = Tagihan::where('id_pelanggan', $p->id_pelanggan)
                ->where('bulan', $currentMonth)
                ->where('tahun', $currentYear)
                ->exists();

            if (!$exists && $p->harga_layanan > 0) {
                if ($p->isBulanGratis($currentMonth, $currentYear)) {
                    Tagihan::create([
                        'id_pelanggan' => $p->id_pelanggan,
                        'bulan' => $currentMonth,
                        'tahun' => $currentYear,
                        'jumlah' => 0,
                        'status' => 'paid',
                        'metode_pembayaran' => 'Bonus Gratis',
                        'paid_at' => $request->created_at ?? now(),
                        'catatan_admin' => 'Bonus Gratis Pemasangan (2 Bulan Pertama)',
                        'created_at' => $request->created_at ?? now(),
                    ]);
                    $generatedCount++;

                    // Kirim Notifikasi WA Promo Gratis
                    if ($p->no_wa && $p->wa_active && \App\Models\Setting::get('wa_billing_notification_enabled', '1') == '1') {
                        try {
                            $monthName = date('F', mktime(0, 0, 0, $currentMonth, 10));
                            $message = "🎉 *PROMO BONUS GRATIS LAYANAN*\n\n";
                            $message .= "Halo *" . $p->kode_pelanggan . "* " . $p->nama_pelanggan . ",\n\n";
                            $message .= "Tagihan internet Anda untuk periode *" . $monthName . " " . $currentYear . "* telah terbit.\n\n";
                            $message .= "Status: *LUNAS (PROMO GRATIS)*\n";
                            $message .= "Jumlah Tagihan: *Rp 0*\n\n";
                            $message .= "Terima kasih telah memilih layanan internet kami! Nikmati koneksi Anda nggih.";
                            $waClient->sendMessage($p->no_wa, ['text' => $message], true);
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Gagal kirim notifikasi tagihan gratis: ' . $e->getMessage());
                        }
                    }
                } else {
                    Tagihan::create([
                        'id_pelanggan' => $p->id_pelanggan,
                        'bulan' => $currentMonth,
                        'tahun' => $currentYear,
                        'jumlah' => $p->harga_layanan,
                        'status' => 'unpaid',
                        'created_at' => $request->created_at ?? now(),
                    ]);
                    $generatedCount++;

                    // Kirim Notifikasi WA jika nomor WA ada dan aktif secara global serta aktif per pelanggan
                    if ($p->no_wa && $p->wa_active && \App\Models\Setting::get('wa_billing_notification_enabled', '1') == '1') {
                        try {
                            $monthName = date('F', mktime(0, 0, 0, $currentMonth, 10));
                            $message = "🔔 *PEMBERITAHUAN TAGIHAN BARU*\n\n";
                            $message .= "Halo *" . $p->kode_pelanggan . "* " . $p->nama_pelanggan . ",\n\n";
                            $message .= "Tagihan internet Anda untuk periode *" . $monthName . " " . $currentYear . "* telah terbit pembayaran maximal per tgl 10.\n\n";
                            $message .= "Jumlah: *Rp " . number_format($p->harga_layanan) . "*\n";
                            $message .= "Status: *BELUM BAYAR*\n\n";
                            $message .= "Silakan lakukan pembayaran agar layanan tetap aktif.\n";
                            $message .= "Ketik *Cek Tagihan* untuk melihat detail pembayaran.\n\n";
                            $message .= "Pembayaran Melalui Rekening  :\n";
                            $message .= "BRI = 621001017663537\n";
                            $message .= "BCA = 7415234155\n";
                            $message .= "Dana = 082187827382\n\n";
                            $message .= "Semua AN/ FACHRUR ROZI\n\n";
                            $message .= "Setelah Melakukan Pembayaran Silahkan Screenshoot / Konfirmasi Pembayaran Melalui Whatsapp wa.me/+6285604118932";

                            $waClient->sendMessage($p->no_wa, ['text' => $message], true);
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Gagal kirim notifikasi tagihan baru: ' . $e->getMessage());
                        }
                    }
                }
            }
        }

        return back()->with('success', "Berhasil membuat {$generatedCount} tagihan baru untuk periode bulan ini.");
    }

    public function confirmPayment(Request $request, Tagihan $tagihan)
    {
        $request->validate([
            'metode_pembayaran' => 'required|string',
            'bukti_bayar' => 'required',
        ]);

        $data = [
            'metode_pembayaran' => $request->metode_pembayaran,
            'status' => 'unpaid', // Tetap unpaid, UI akan mendeteksi bukti_bayar untuk menampilkan 'Menunggu Verifikasi'
        ];

        if ($request->hasFile('bukti_bayar')) {
            $file = $request->file('bukti_bayar');
            $extension = strtolower($file->getClientOriginalExtension());
            $allowedExtensions = ['jpeg', 'png', 'jpg', 'gif', 'pdf'];
            if (!in_array($extension, $allowedExtensions)) {
                return back()->withErrors(['bukti_bayar' => 'Bukti pembayaran harus berupa dokumen gambar (jpg, png, jpeg) atau berkas PDF!']);
            }
            if ($file->getSize() > 3 * 1024 * 1024) {
                return back()->withErrors(['bukti_bayar' => 'Ukuran file bukti pembayaran maksimal 3MB!']);
            }
            // Move file manually to public storage to bypass Laravel's extension guesser (which requires php_fileinfo extension)
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $targetDir = storage_path('app/public/bukti_bayar');
            if (!file_exists($targetDir)) {
                @mkdir($targetDir, 0755, true);
                @chmod($targetDir, 0755);
            }
            $file->move($targetDir, $filename);
            @chmod($targetDir . '/' . $filename, 0644);
            $path = 'bukti_bayar/' . $filename;
            $data['bukti_bayar'] = $path;
        }

        $tagihan->update($data);

        return back()->with('success', 'Bukti pembayaran berhasil diunggah. Menunggu konfirmasi admin.');
    }

    public function editBuktiBayar(Request $request, Tagihan $tagihan)
    {
        // Task 2.1: Permission checking (customer owns OR admin/manager)
        $user = auth()->user();
        $isAdmin = in_array($user->id_role, [1, 2]); // Admin or Manager
        $isOwner = $tagihan->pelanggan && $tagihan->pelanggan->id_user == $user->id;

        if (!$isAdmin && !$isOwner) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit bukti bayar ini');
        }

        // Task 2.2: File validation - Manual validation to avoid fileinfo dependency
        if (!$request->hasFile('bukti_bayar')) {
            return back()->withErrors(['bukti_bayar' => 'File bukti pembayaran harus dilampirkan.']);
        }

        $file = $request->file('bukti_bayar');
        
        // Validate extension
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['jpeg', 'png', 'jpg', 'gif', 'pdf'];
        
        if (!in_array($extension, $allowedExtensions)) {
            return back()->withErrors(['bukti_bayar' => 'Bukti pembayaran harus berupa dokumen gambar (jpg, png, jpeg, gif) atau berkas PDF!']);
        }
        
        // Validate file size (max 3MB)
        if ($file->getSize() > 3 * 1024 * 1024) {
            return back()->withErrors(['bukti_bayar' => 'Ukuran file bukti pembayaran maksimal 3MB!']);
        }

        // Validate metode_pembayaran if provided
        if ($request->filled('metode_pembayaran') && strlen($request->metode_pembayaran) > 255) {
            return back()->withErrors(['metode_pembayaran' => 'Metode pembayaran maksimal 255 karakter.']);
        }

        // Task 2.3: File deletion and upload logic
        $oldFile = $tagihan->bukti_bayar;

        // Delete old file if exists
        if ($oldFile) {
            $filePath = storage_path('app/public/' . $oldFile);
            if (file_exists($filePath)) {
                $deleted = @unlink($filePath);
                if (!$deleted) {
                    \Illuminate\Support\Facades\Log::warning('Failed to delete old payment proof: ' . $filePath);
                }
            }
        }

        // Upload new file with unique filename
        $filename = time() . '_' . uniqid() . '.' . $extension;
        $targetDir = storage_path('app/public/bukti_bayar');
        
        if (!file_exists($targetDir)) {
            @mkdir($targetDir, 0755, true);
            @chmod($targetDir, 0755);
        }
        
        try {
            $file->move($targetDir, $filename);
            @chmod($targetDir . '/' . $filename, 0644);
            $path = 'bukti_bayar/' . $filename;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to upload payment proof: ' . $e->getMessage());
            return back()->withErrors(['bukti_bayar' => 'Gagal mengunggah file. Silakan coba lagi.'])->withInput();
        }

        // Task 2.4: Status update logic based on user role
        $data = [
            'bukti_bayar' => $path,
        ];

        // Update metode_pembayaran if provided
        if ($request->filled('metode_pembayaran')) {
            $data['metode_pembayaran'] = $request->metode_pembayaran;
        }

        // Status update based on role
        if (!$isAdmin) {
            // Customer edits: maintain status as 'unpaid' for admin verification
            $data['status'] = 'unpaid';
        } else {
            // Admin edits: can optionally verify and set to 'paid'
            if ($request->input('verify_payment')) {
                $data['status'] = 'paid';
                $data['paid_at'] = now();
            } else if ($request->has('status')) {
                // Allow admin to explicitly set status
                $data['status'] = $request->status;
                if ($request->status === 'paid' && !$tagihan->paid_at) {
                    $data['paid_at'] = now();
                    }
                }
                // If no explicit status change, preserve existing status
            }

            // Task 2.5: Update database and log activity
            $tagihan->update($data);

        // Task 2.5: Update database and log activity
        $tagihan->update($data);

        // Log activity
        try {
            $actionDescription = $oldFile ? 'mengganti ' . basename($oldFile) : 'menambahkan bukti baru';
            \App\Helpers\ActivityLogger::log(
                'Mengedit bukti bayar tagihan #' . $tagihan->id_tagihan . 
                ' (' . ($tagihan->pelanggan ? $tagihan->pelanggan->nama_pelanggan : 'Umum') . ') ' .
                $actionDescription,
                'tagihan'
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to log activity for payment proof edit: ' . $e->getMessage());
            // Continue - logging failure should not block the operation
        }

        return back()->with('success', 'Bukti pembayaran berhasil diperbarui.');
    }

    public function showEditBuktiBayar(Tagihan $tagihan)
    {
        // Permission checking (customer owns OR admin/manager)
        $user = auth()->user();
        $isAdmin = in_array($user->id_role, [1, 2]); // Admin or Manager
        $isOwner = $tagihan->pelanggan && $tagihan->pelanggan->id_user == $user->id;

        if (!$isAdmin && !$isOwner) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit bukti bayar ini');
        }

        return view('content.billing.edit-bukti-bayar', compact('tagihan'));
    }

    public function verifikasi(Request $request, Tagihan $tagihan)
    {
        // Admin or Manager
        if (!in_array(auth()->user()->id_role, [1, 2])) {
            abort(403);
        }

        $tagihan->update([
            'status' => 'paid',
            'paid_at' => $request->paid_at ?? now(),
            'metode_pembayaran' => $request->metode_pembayaran ?? $tagihan->metode_pembayaran,
            'catatan_admin' => $request->catatan_admin
        ]);

        // Log the activity
        try {
            \App\Helpers\ActivityLogger::log(
                'Memverifikasi manual pembayaran tagihan #' . $tagihan->id_tagihan . ' (' . ($tagihan->pelanggan ? $tagihan->pelanggan->nama_pelanggan : 'Umum') . ') sebesar Rp ' . number_format($tagihan->jumlah, 0, ',', '.') . ($tagihan->metode_pembayaran ? ' via ' . $tagihan->metode_pembayaran : ''),
                'tagihan'
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Gagal mencatat log aktivitas verifikasi manual: " . $e->getMessage());
        }

        $pelanggan = $tagihan->pelanggan;
        $mikrotikWarning = null;

        if ($pelanggan && $pelanggan->id_router) {
            // Tandai aktif di DB terlebih dahulu (pembayaran sudah diverifikasi admin)
            $pelanggan->update(['is_active' => true, 'is_isolated' => false]);

            // Sinkronisasi ke MikroTik (best-effort)
            try {
                $mikrotikService = app(\App\Services\MikrotikService::class);
                $success = $mikrotikService->setSecretStatus(
                    $pelanggan->router,
                    $pelanggan->mikrotik_username ?: $pelanggan->kode_pelanggan,
                    $pelanggan->mikrotik_type,
                    false,
                    $pelanggan->ip_address
                );
                if (!$success) {
                    $mikrotikWarning = '⚠️ Catatan: Tagihan terverifikasi, namun sinkronisasi otomatis ke MikroTik gagal. Router mungkin offline. Coba jalankan "Aktifkan Pelanggan Lunas" dari menu Pengaturan.';
                    \Illuminate\Support\Facades\Log::warning("Billing Verifikasi: MikroTik sync failed for {$pelanggan->kode_pelanggan}. Customer marked active in DB but router not updated.");
                }
            } catch (\Exception $e) {
                $mikrotikWarning = '⚠️ Catatan: Tagihan terverifikasi, namun terjadi error saat sinkronisasi MikroTik: ' . $e->getMessage();
                \Illuminate\Support\Facades\Log::error("Billing Verifikasi: MikroTik exception for {$pelanggan->kode_pelanggan}: " . $e->getMessage());
            }
        } elseif ($pelanggan) {
            // Tidak ada router terkonfigurasi, tetap tandai aktif
            $pelanggan->update(['is_active' => true, 'is_isolated' => false]);
        }

        // Kirim Notifikasi WA setelah response (non-blocking)
        if ($pelanggan && $pelanggan->no_wa && $pelanggan->wa_active && \App\Models\Setting::get('wa_billing_notification_enabled', '1') == '1') {
            $tid = $tagihan->id_tagihan;
            app()->terminating(function () use ($tid) {
                try {
                    $t = \App\Models\Tagihan::find($tid);
                    if ($t) (new \App\Services\WhatsappClient())->sendReceipt($t, true);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal kirim notifikasi WA verifikasi: ' . $e->getMessage());
                }
            });
        }

        $successMsg = 'Tagihan berhasil diverifikasi dan layanan diaktifkan.';
        if ($mikrotikWarning) {
            return back()->with('success', $successMsg)->with('warning', $mikrotikWarning);
        }

        return back()->with('success', $successMsg);
    }

    public function settings()
    {
        if (!in_array(auth()->user()->id_role, [1, 2])) abort(403);
        
        return view('content.billing.settings');
    }

    public function updateSettings(Request $request)
    {
        if (!in_array(auth()->user()->id_role, [1, 2])) abort(403);

        // Form 1: Konfigurasi Metode Pembayaran
        if ($request->has('midtrans_merchant_id') || $request->has('manual_methods') || $request->has('gateway_enabled') || $request->has('manual_enabled')) {
            \App\Models\Setting::set('payment_gateway_enabled', $request->has('gateway_enabled') ? '1' : '0', 'payment');
            \App\Models\Setting::set('midtrans_merchant_id', trim($request->midtrans_merchant_id), 'payment');
            \App\Models\Setting::set('midtrans_client_key', trim($request->midtrans_client_key), 'payment');
            \App\Models\Setting::set('midtrans_server_key', trim($request->midtrans_server_key), 'payment');
            \App\Models\Setting::set('midtrans_is_production', $request->has('midtrans_is_production') ? '1' : '0', 'payment');
            \App\Models\Setting::set('payment_fee', $request->payment_fee ?? '0', 'payment');
            \App\Models\Setting::set('manual_payment_enabled', $request->has('manual_enabled') ? '1' : '0', 'payment');
            \App\Models\Setting::set('manual_payment_methods', $request->manual_methods, 'payment');
            \App\Models\Setting::set('manual_bank_info', $request->bank_info, 'payment');
        }

        // Form 2: Otomatisasi Tagihan & Isolir
        if ($request->has('billing_generate_date') || $request->has('billing_isolir_date')) {
            \App\Models\Setting::set('billing_auto_generate_enabled', $request->has('auto_generate_enabled') ? '1' : '0', 'automation');
            \App\Models\Setting::set('billing_generate_date', $request->billing_generate_date ?? '1', 'automation');
            \App\Models\Setting::set('billing_start_date', $request->billing_start_date ?? '1', 'automation');
            \App\Models\Setting::set('billing_isolir_date', $request->billing_isolir_date ?? '10', 'automation');
            \App\Models\Setting::set('billing_isolir_hour', $request->billing_isolir_hour ?? '12', 'automation');
            \App\Models\Setting::set('billing_auto_isolir_enabled', $request->has('auto_isolir_enabled') ? '1' : '0', 'automation');
            \App\Models\Setting::set('billing_reminder_enabled', $request->has('reminder_enabled') ? '1' : '0', 'automation');
            \App\Models\Setting::set('billing_reminder_date', $request->billing_reminder_date ?? '5', 'automation');
        }

        // Form 3: Manajemen Notifikasi WhatsApp (Global)
        if ($request->has('wa_billing_switch_present')) {
            \App\Models\Setting::set('wa_billing_notification_enabled', $request->has('wa_billing_notification_enabled') ? '1' : '0', 'automation');
        }

        return back()->with('success', 'Pengaturan pembayaran dan otomatisasi berhasil diperbarui.');
    }

    public function clearAllPhoneNumbers()
    {
        if (!in_array(auth()->user()->id_role, [1, 2])) abort(403);

        try {
            \App\Models\Pelanggan::query()->update(['no_wa' => null]);
            return back()->with('success', 'Semua nomor HP pelanggan berhasil dimatikan/dikosongkan secara massal.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mematikan nomor HP pelanggan: ' . $e->getMessage());
        }
    }

    public function downloadReceipt(Tagihan $tagihan)
    {
        // For security, maybe check if user is admin or the owner of the bill
        $user = auth()->user();
        if ($user && !in_array($user->id_role, [1, 2]) && $tagihan->pelanggan->id_user != $user->id) {
            abort(403);
        }

        if ($tagihan->status !== 'paid') {
            return back()->with('error', 'Nota hanya tersedia untuk tagihan yang sudah lunas.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('content.billing.receipt-pdf', compact('tagihan'))
            ->setOption('isRemoteEnabled', true);
        
        $fileName = 'Nota-' . $tagihan->pelanggan->kode_pelanggan . '-' . date('M-Y', mktime(0, 0, 0, $tagihan->bulan, 10)) . '.pdf';
        
        return $pdf->download($fileName);
    }
    public function payCash(Tagihan $tagihan)
    {
        if (!in_array(auth()->user()->id_role, [1, 2])) abort(403);

        $tagihan->update([
            'status' => 'paid',
            'metode_pembayaran' => 'Cash',
            'paid_at' => now(),
        ]);

        $pelanggan = $tagihan->pelanggan;
        $mikrotikWarning = null;

        if ($pelanggan && $pelanggan->id_router) {
            // Tandai aktif di DB terlebih dahulu (pembayaran sudah dikonfirmasi admin)
            $pelanggan->update(['is_active' => true, 'is_isolated' => false]);

            // Sinkronisasi ke MikroTik (best-effort)
            try {
                $mikrotikService = app(\App\Services\MikrotikService::class);
                $success = $mikrotikService->setSecretStatus(
                    $pelanggan->router,
                    $pelanggan->mikrotik_username ?: $pelanggan->kode_pelanggan,
                    $pelanggan->mikrotik_type,
                    false,
                    $pelanggan->ip_address
                );
                if (!$success) {
                    $mikrotikWarning = '⚠️ Catatan: Pembayaran berhasil dicatat, namun sinkronisasi otomatis ke MikroTik gagal. Router mungkin offline. Coba jalankan "Aktifkan Pelanggan Lunas" dari menu Pengaturan.';
                    \Illuminate\Support\Facades\Log::warning("Billing Cash: MikroTik sync failed for {$pelanggan->kode_pelanggan}. Customer marked active in DB but router not updated.");
                }
            } catch (\Exception $e) {
                $mikrotikWarning = '⚠️ Catatan: Pembayaran berhasil dicatat, namun terjadi error saat sinkronisasi MikroTik: ' . $e->getMessage();
                \Illuminate\Support\Facades\Log::error("Billing Cash: MikroTik exception for {$pelanggan->kode_pelanggan}: " . $e->getMessage());
            }
        } elseif ($pelanggan) {
            // Tidak ada router terkonfigurasi, tetap tandai aktif
            $pelanggan->update(['is_active' => true, 'is_isolated' => false]);
        }

        // Kirim Notifikasi WA Kwitansi Lunas setelah response (non-blocking)
        if ($pelanggan && $pelanggan->no_wa && $pelanggan->wa_active && \App\Models\Setting::get('wa_billing_notification_enabled', '1') == '1') {
            $tid = $tagihan->id_tagihan;
            app()->terminating(function () use ($tid) {
                try {
                    $t = \App\Models\Tagihan::find($tid);
                    if ($t) (new \App\Services\WhatsappClient())->sendReceipt($t, true);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal kirim notifikasi WA Cash: ' . $e->getMessage());
                }
            });
        }

        \App\Helpers\ActivityLogger::log('Mengonfirmasi pembayaran Cash untuk tagihan #' . $tagihan->id_tagihan . ' (' . ($tagihan->pelanggan ? $tagihan->pelanggan->nama_pelanggan : 'Umum') . ') sebesar Rp ' . number_format($tagihan->jumlah, 0, ',', '.'), 'tagihan');

        $successMsg = 'Pembayaran Cash berhasil dikonfirmasi, WiFi diaktifkan, dan struk terkirim otomatis ke WhatsApp pelanggan!';
        if ($mikrotikWarning) {
            return back()->with('success', $successMsg)->with('warning', $mikrotikWarning);
        }

        return back()->with('success', $successMsg);
    }

    public function sendReceiptWa(Tagihan $tagihan)
    {
        if (!in_array(auth()->user()->id_role, [1, 2])) abort(403);
        
        $pelanggan = $tagihan->pelanggan;
        if (!$pelanggan || !$pelanggan->no_wa) {
            return back()->with('error', 'Pelanggan tidak memiliki nomor WhatsApp yang valid.');
        }

        try {
            $waClient = new \App\Services\WhatsappClient();
            $waClient->sendReceipt($tagihan, true);
            return back()->with('success', 'Kwitansi pembayaran berhasil dikirim ke WhatsApp pelanggan!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengirim kwitansi WhatsApp: ' . $e->getMessage());
        }
    }

    public function runIsolirSync(Request $request)
    {
        if (!in_array(auth()->user()->id_role, [1, 2])) abort(403);

        $type = $request->query('type', 'all'); // 'disable', 'enable', 'all', 'reminder'
        
        try {
            if ($type == 'reminder') {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    pclose(popen("cmd /c start /B php artisan billing:remind --force", "r"));
                } else {
                    exec("php artisan billing:remind --force > /dev/null 2>&1 &");
                }
                return back()->with('success', 'Pengiriman pengingat WhatsApp massal telah dijalankan di latar belakang (background). Proses ini akan berjalan otomatis. Silakan cek berkas log Laravel.');
            }

            if ($type == 'disable') {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    pclose(popen("cmd /c start /B php artisan billing:disable-unpaid --force", "r"));
                } else {
                    exec("php artisan billing:disable-unpaid --force > /dev/null 2>&1 &");
                }
                return back()->with('success', 'Proses isolir otomatis pelanggan belum bayar sedang berjalan di latar belakang.');
            }
            
            if ($type == 'enable') {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    pclose(popen("cmd /c start /B php artisan billing:enable-paid --force", "r"));
                } else {
                    exec("php artisan billing:enable-paid --force > /dev/null 2>&1 &");
                }
                return back()->with('success', 'Proses aktivasi otomatis pelanggan lunas sedang berjalan di latar belakang.');
            }

            if ($type == 'all') {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    pclose(popen("cmd /c start /B php artisan billing:disable-unpaid --force", "r"));
                    pclose(popen("cmd /c start /B php artisan billing:enable-paid --force", "r"));
                } else {
                    exec("php artisan billing:disable-unpaid --force > /dev/null 2>&1 &");
                    exec("php artisan billing:enable-paid --force > /dev/null 2>&1 &");
                }
                return back()->with('success', 'Semua proses sinkronisasi isolir dan aktivasi otomatis sedang berjalan di latar belakang.');
            }
            
            return back()->with('error', 'Parameter tipe sinkronisasi tidak valid.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menjalankan sinkronisasi: ' . $e->getMessage());
        }
    }
}

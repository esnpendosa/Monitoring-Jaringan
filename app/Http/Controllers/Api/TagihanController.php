<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tagihan;
use App\Models\Pelanggan;
use App\Models\Setting;
use App\Http\Resources\TagihanResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TagihanController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of bills with filters (status, month, year).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tagihan::with('pelanggan');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('bulan')) {
            $query->where('bulan', $request->input('bulan'));
        }

        if ($request->filled('tahun')) {
            $query->where('tahun', $request->input('tahun'));
        }

        $perPage = $request->input('per_page', 10);
        $tagihan = $query->paginate($perPage);

        return $this->successResponse(
            TagihanResource::collection($tagihan),
            'Berhasil mengambil daftar tagihan'
        );
    }

    /**
     * Display bills that are due today.
     */
    public function jatuhTempoHariIni(Request $request): JsonResponse
    {
        $day = $request->query('day', now()->day);
        $dueDay = (int) Setting::get('billing_isolir_date', '10');

        // Check if today matches the billing isolir date
        if ($day == $dueDay || $request->boolean('force')) {
            $tagihan = Tagihan::where('status', 'unpaid')
                ->where('bulan', now()->month)
                ->where('tahun', now()->year)
                ->with('pelanggan')
                ->get();
        } else {
            $tagihan = collect();
        }

        return $this->successResponse(
            TagihanResource::collection($tagihan),
            'Berhasil mengambil tagihan jatuh tempo hari ini'
        );
    }

    /**
     * Mark a bill as paid manually (Admin only).
     */
    public function tandaiLunas(Request $request, $id): JsonResponse
    {
        $tagihan = Tagihan::find($id);

        if (!$tagihan) {
            return $this->errorResponse('Tagihan tidak ditemukan', 404);
        }

        if ($tagihan->status === 'paid') {
            return $this->errorResponse('Tagihan sudah lunas sebelumnya', 400);
        }

        $validator = Validator::make($request->all(), [
            'metode_pembayaran' => 'nullable|string',
            'catatan_admin' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi Gagal', 422, $validator->errors());
        }

        $metode = $request->input('metode_pembayaran', 'Manual via API');
        $catatan = $request->input('catatan_admin', 'Ditandai lunas via REST API');

        // Update tagihan status
        $tagihan->update([
            'status' => 'paid',
            'paid_at' => now(),
            'metode_pembayaran' => $metode,
            'catatan_admin' => $catatan
        ]);

        $pelanggan = $tagihan->pelanggan;
        $mikrotikSynced = true;
        $syncMessage = '';

        if ($pelanggan) {
            // Update active status in database
            $pelanggan->update(['is_active' => true, 'is_isolated' => false]);

            // Sync with MikroTik if router is configured
            if ($pelanggan->id_router) {
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
                        $mikrotikSynced = false;
                        $syncMessage = 'Sinkronisasi MikroTik gagal, silakan periksa router Anda.';
                        Log::warning("API Billing: MikroTik sync failed for customer {$pelanggan->kode_pelanggan}");
                    }
                } catch (\Exception $e) {
                    $mikrotikSynced = false;
                    $syncMessage = 'Error MikroTik: ' . $e->getMessage();
                    Log::error("API Billing: MikroTik exception for customer {$pelanggan->kode_pelanggan}: " . $e->getMessage());
                }
            }
        }

        // Kirim receipt WA setelah response (non-blocking)
        if ($pelanggan && $pelanggan->no_wa && $pelanggan->wa_active && Setting::get('wa_billing_notification_enabled', '1') == '1') {
            $tid = $tagihan->id_tagihan;
            app()->terminating(function () use ($tid) {
                try {
                    $t = \App\Models\Tagihan::find($tid);
                    if ($t) (new \App\Services\WhatsappClient())->sendReceipt($t, true);
                } catch (\Exception $e) {
                    Log::error('API Billing: Gagal kirim receipt WhatsApp: ' . $e->getMessage());
                }
            });
        }

        // Log the activity
        try {
            \App\Helpers\ActivityLogger::log(
                'Memverifikasi lunas tagihan #' . $tagihan->id_tagihan . ' (' . ($pelanggan ? $pelanggan->nama_pelanggan : 'Umum') . ') sebesar Rp ' . number_format($tagihan->jumlah, 0, ',', '.') . ' via API',
                'tagihan'
            );
        } catch (\Exception $e) {
            Log::error("API Billing: Gagal mencatat log aktivitas: " . $e->getMessage());
        }

        return $this->successResponse(
            new TagihanResource($tagihan),
            'Tagihan berhasil ditandai lunas. ' . $syncMessage
        );
    }

    /**
     * Get billing statistics.
     */
    public function statistik(): JsonResponse
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $pendapatanBulanIni = Tagihan::where('status', 'paid')
            ->where('bulan', $currentMonth)
            ->where('tahun', $currentYear)
            ->sum('jumlah');

        $tunggakanBulanIni = Tagihan::where('status', 'unpaid')
            ->where('bulan', $currentMonth)
            ->where('tahun', $currentYear)
            ->sum('jumlah');

        $totalTunggakanSemua = Tagihan::where('status', 'unpaid')
            ->sum('jumlah');

        $lunasCount = Tagihan::where('status', 'paid')
            ->where('bulan', $currentMonth)
            ->where('tahun', $currentYear)
            ->count();

        $unpaidCount = Tagihan::where('status', 'unpaid')
            ->where('bulan', $currentMonth)
            ->where('tahun', $currentYear)
            ->count();

        $stats = [
            'pendapatan_bulan_ini' => (int) $pendapatanBulanIni,
            'tunggakan_bulan_ini' => (int) $tunggakanBulanIni,
            'total_tunggakan_semua' => (int) $totalTunggakanSemua,
            'pelanggan_lunas_bulan_ini' => $lunasCount,
            'pelanggan_belum_lunas_bulan_ini' => $unpaidCount,
        ];

        return $this->successResponse($stats, 'Berhasil mengambil statistik tagihan');
    }

    /**
     * Edit payment proof (bukti bayar) for a billing record.
     */
    public function editBuktiBayar(Request $request, $id): JsonResponse
    {
        // Find Tagihan by ID
        $tagihan = Tagihan::with('pelanggan')->find($id);

        if (!$tagihan) {
            return $this->errorResponse('Tagihan tidak ditemukan', 404);
        }

        // Task 4.1: Permission checking (customer owns OR admin/manager)
        $user = auth()->user();
        $isAdmin = in_array($user->id_role, [1, 2]); // Admin or Manager
        $isOwner = $tagihan->pelanggan && $tagihan->pelanggan->id_user == $user->id;

        if (!$isAdmin && !$isOwner) {
            return $this->errorResponse('Anda tidak memiliki akses untuk mengedit bukti bayar ini', 403);
        }

        // Task 4.2: File validation - Manual validation to avoid fileinfo dependency
        if (!$request->hasFile('bukti_bayar')) {
            return $this->errorResponse('Validasi gagal', 422, [
                'bukti_bayar' => ['File bukti pembayaran harus dilampirkan.']
            ]);
        }

        $file = $request->file('bukti_bayar');
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['jpeg', 'png', 'jpg', 'gif', 'pdf'];
        
        // Additional validation
        if (!in_array($extension, $allowedExtensions)) {
            return $this->errorResponse('Validasi gagal', 422, [
                'bukti_bayar' => ['Bukti pembayaran harus berupa dokumen gambar (jpg, png, jpeg, gif) atau berkas PDF!']
            ]);
        }
        if ($file->getSize() > 3 * 1024 * 1024) {
            return $this->errorResponse('Validasi gagal', 422, [
                'bukti_bayar' => ['Ukuran file bukti pembayaran maksimal 3MB!']
            ]);
        }

        // Validate metode_pembayaran if provided
        if ($request->filled('metode_pembayaran') && strlen($request->metode_pembayaran) > 255) {
            return $this->errorResponse('Validasi gagal', 422, [
                'metode_pembayaran' => ['Metode pembayaran maksimal 255 karakter.']
            ]);
        }

        // File deletion and upload logic
        $oldFile = $tagihan->bukti_bayar;
        
        // Delete old file if exists
        if ($oldFile) {
            $filePath = storage_path('app/public/' . $oldFile);
            if (file_exists($filePath)) {
                $deleted = @unlink($filePath);
                if (!$deleted) {
                    Log::warning('API: Failed to delete old payment proof: ' . $filePath);
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
            Log::error('API: Failed to upload payment proof: ' . $e->getMessage());
            return $this->errorResponse('Gagal mengunggah file. Silakan coba lagi.', 500);
        }

        // Status update logic based on user role
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

        // Update database and log activity
        $tagihan->update($data);

        // Log activity
        try {
            $actionDescription = $oldFile ? 'mengganti ' . basename($oldFile) : 'menambahkan bukti baru';
            \App\Helpers\ActivityLogger::log(
                'Mengedit bukti bayar tagihan #' . $tagihan->id_tagihan . 
                ' (' . ($tagihan->pelanggan ? $tagihan->pelanggan->nama_pelanggan : 'Umum') . ') ' .
                $actionDescription . ' via API',
                'tagihan'
            );
        } catch (\Exception $e) {
            Log::error('API: Failed to log activity for payment proof edit: ' . $e->getMessage());
            // Continue - logging failure should not block the operation
        }

        // Return success response with updated billing record data
        return $this->successResponse([
            'id_tagihan' => $tagihan->id_tagihan,
            'bukti_bayar' => $tagihan->bukti_bayar,
            'metode_pembayaran' => $tagihan->metode_pembayaran,
            'status' => $tagihan->status,
        ], 'Bukti pembayaran berhasil diperbarui');
    }
}

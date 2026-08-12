<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Http\Resources\PelangganResource;
use App\Http\Resources\TagihanResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PelangganController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the customers with pagination, search, and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pelanggan::query();

        // Search by name or phone/whatsapp number
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('no_wa', 'like', "%{$search}%")
                  ->orWhere('kode_pelanggan', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'aktif') {
                $query->where('is_active', true);
            } elseif ($status === 'isolir') {
                $query->where('is_active', false);
            }
        }

        $perPage = $request->input('per_page', 10);
        $pelanggan = $query->paginate($perPage);

        return $this->successResponse(
            PelangganResource::collection($pelanggan),
            'Berhasil mengambil daftar pelanggan'
        );
    }

    /**
     * Display the specified customer.
     */
    public function show($id): JsonResponse
    {
        $pelanggan = Pelanggan::find($id);

        if (!$pelanggan) {
            return $this->errorResponse('Pelanggan tidak ditemukan', 404);
        }

        return $this->successResponse(
            new PelangganResource($pelanggan),
            'Berhasil mengambil detail pelanggan'
        );
    }

    /**
     * Display the billing history for the specified customer.
     */
    public function tagihan($id): JsonResponse
    {
        $pelanggan = Pelanggan::find($id);

        if (!$pelanggan) {
            return $this->errorResponse('Pelanggan tidak ditemukan', 404);
        }

        $tagihan = Tagihan::where('id_pelanggan', $id)
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->get();

        return $this->successResponse(
            TagihanResource::collection($tagihan),
            'Berhasil mengambil riwayat tagihan pelanggan'
        );
    }

    /**
     * Display the active bill for the current billing cycle (latest unpaid bill).
     */
    public function tagihanAktif($id): JsonResponse
    {
        $pelanggan = Pelanggan::find($id);

        if (!$pelanggan) {
            return $this->errorResponse('Pelanggan tidak ditemukan', 404);
        }

        // Active bill is the latest unpaid bill
        $tagihan = Tagihan::where('id_pelanggan', $id)
            ->where('status', 'unpaid')
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->first();

        if (!$tagihan) {
            return $this->successResponse(null, 'Tidak ada tagihan aktif (belum dibayar) untuk pelanggan ini');
        }

        return $this->successResponse(
            new TagihanResource($tagihan),
            'Berhasil mengambil tagihan aktif pelanggan'
        );
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Http\Resources\PelangganResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PetaController extends Controller
{
    use ApiResponse;

    /**
     * Get all customers with coordinates for map markers.
     */
    public function pelanggan(): JsonResponse
    {
        $pelanggan = Pelanggan::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', '')
            ->where('longitude', '!=', '')
            ->get();

        return $this->successResponse(
            PelangganResource::collection($pelanggan),
            'Berhasil mengambil data koordinat pelanggan untuk peta'
        );
    }
}

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PelangganController;
use App\Http\Controllers\Api\TagihanController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PetaController;
use App\Http\Controllers\Api\LaporanController as ApiLaporanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider or bootstrap/app.php
| within a group which is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Auth Routes (Public login, protected logout & me)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Protected routes with Sanctum auth and Rate Limiting (60 requests/minute)
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    
    // Pelanggan endpoints
    Route::prefix('pelanggan')->group(function () {
        Route::get('/', [PelangganController::class, 'index']);
        Route::get('{id}', [PelangganController::class, 'show']);
        Route::get('{id}/tagihan', [PelangganController::class, 'tagihan']);
        Route::get('{id}/tagihan/aktif', [PelangganController::class, 'tagihanAktif']);
    });

    // Tagihan & Pembayaran endpoints
    Route::prefix('tagihan')->group(function () {
        Route::get('/', [TagihanController::class, 'index']);
        Route::get('jatuh-tempo-hari-ini', [TagihanController::class, 'jatuhTempoHariIni']);
        Route::get('statistik', [TagihanController::class, 'statistik']);
        Route::patch('{id}/tandai-lunas', [TagihanController::class, 'tandaiLunas']);
        Route::post('{id}/edit-bukti-bayar', [TagihanController::class, 'editBuktiBayar']);
    });

    // Dashboard summary
    Route::get('dashboard', [DashboardController::class, 'index']);

    // GIS/Peta endpoints
    Route::get('peta/pelanggan', [PetaController::class, 'pelanggan']);

    // Laporan endpoints
    Route::get('laporan/rekap-pembayaran', [ApiLaporanController::class, 'rekapPembayaran']);
});

<?php

/**
 * KnnController.php — Controller Klasifikasi KNN
 *
 * Skripsi: Optimasi Web GIS Sistem Manajemen Jaringan WiFi Menggunakan KNN
 * Mahasiswa: Muhammad As'ad Muhibbin Akbar — NIM 220602077
 *
 * Controller ini menghubungkan antarmuka UI dengan logika KNN di KnnService.php
 * Semua perhitungan matematis dilakukan di KnnService — controller hanya
 * mengatur alur request/response.
 */

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\KnnHasil;
use App\Services\KnnService;
use Illuminate\Http\Request;

class KnnController extends Controller
{
    /**
     * Dependency Injection: KnnService di-inject otomatis oleh Laravel IoC Container
     * KnnService berisi semua logika perhitungan Euclidean Distance 4D dan voting KNN
     */
    protected $knnService;

    public function __construct(KnnService $knnService)
    {
        $this->knnService = $knnService; // simpan instance service untuk digunakan di semua method
    }

    /**
     * ─────────────────────────────────────────────────────────────────────────
     * METHOD: index() — Halaman Utama KNN
     * ─────────────────────────────────────────────────────────────────────────
     * Menampilkan:
     *   - Daftar semua pelanggan (untuk pilih data uji)
     *   - Riwayat hasil klasifikasi KNN (Tabel 4.5, 4.6, 4.7 di skripsi)
     *   - Tabel akurasi, confusion matrix, F1-Score
     *
     * Route: GET /knn
     */
    /**
     * ─────────────────────────────────────────────────────────────────────────
     * METHOD: index() — Halaman Utama KNN
     * ─────────────────────────────────────────────────────────────────────────
     * Menampilkan:
     *   - Daftar semua pelanggan (untuk pilih data uji)
     *   - Riwayat hasil klasifikasi KNN (Tabel 4.5, 4.6, 4.7 di skripsi)
     *   - Tabel akurasi, confusion matrix, F1-Score
     *
     * Route: GET /knn
     */
    public function index()
    {
        // Ambil semua pelanggan sebagai kandidat data uji
        $pelanggan = Pelanggan::all();

        // Cari K terbaik secara otomatis untuk K=1 sampai 9
        $bestKData = $this->knnService->findBestK();
        $bestK = $bestKData['best_k'];
        $bestAcc = $bestKData['best_acc'];
        $evaluasi = $bestKData['all'];

        // Ambil riwayat hasil KNN dengan relasi lengkap:
        // - pelanggan: nama pelanggan yang diklasifikasi
        // - parameter: nilai K yang digunakan
        // - details.tetangga: detail K tetangga terdekat (Rank, Jarak, Label)
        $hasil = KnnHasil::with(['pelanggan', 'parameter', 'details.tetangga'])
            ->latest()  // urutkan dari yang terbaru
            ->get();

        return view('content.knn.index', compact('pelanggan', 'hasil', 'bestK', 'bestAcc', 'evaluasi'));
    }

    /**
     * ─────────────────────────────────────────────────────────────────────────
     * METHOD: process() — Klasifikasi Satu Pelanggan
     * ─────────────────────────────────────────────────────────────────────────
     * Melakukan klasifikasi KNN untuk SATU pelanggan yang dipilih dari form.
     * Ini otomatis mencari K terbaik dari K=1..9 dengan akurasi tertinggi.
     *
     * Route: POST /knn/process
     */
    public function process(Request $request)
    {
        // Validasi input form — pastikan data pelanggan ada di DB
        $request->validate([
            'id_pelanggan' => 'required|exists:pelanggan,id_pelanggan', // data uji harus ada di DB
        ]);

        // Cari K terbaik secara otomatis untuk K=1 sampai 9
        $bestKData = $this->knnService->findBestK();
        $bestK = $bestKData['best_k'];
        $bestAcc = $bestKData['best_acc'];

        // Jalankan klasifikasi KNN menggunakan K optimal:
        $hasil = $this->knnService->classify($request->id_pelanggan, $bestK);

        // Jika gagal (data latih tidak cukup), kembalikan pesan error
        if (!$hasil) {
            return back()->with('error', 'Gagal memproses KNN. Pastikan data training mencukupi.');
        }

        // Berhasil — tampilkan label hasil prediksi (HIGH/MEDIUM/LOW) dan informasi K yang terpilih
        return back()->with('success', "Pelanggan berhasil diklasifikasikan sebagai: {$hasil->label_hasil} menggunakan K terbaik = {$bestK} (Akurasi: {$bestAcc}%)");
    }

    /**
     * ─────────────────────────────────────────────────────────────────────────
     * METHOD: batchProcess() — Klasifikasi Semua Pelanggan Sekaligus
     * ─────────────────────────────────────────────────────────────────────────
     * Melakukan klasifikasi KNN untuk SEMUA pelanggan secara batch menggunakan K terbaik.
     *
     * Route: POST /knn/batch
     */
    public function batchProcess(Request $request)
    {
        // Cari K terbaik secara otomatis untuk K=1 sampai 9
        $bestKData = $this->knnService->findBestK();
        $bestK = $bestKData['best_k'];
        $bestAcc = $bestKData['best_acc'];

        // ── Validasi: cek apakah data latih mencukupi ─────────────────────────
        $trainingCount = Pelanggan::whereNotNull('prioritas_label')->count();
        if ($trainingCount < $bestK) {
            return back()->with('error',
                "Data training tidak cukup. Dibutuhkan minimal $bestK pelanggan " .
                "yang sudah memiliki label (High/Medium/Low) untuk menggunakan K={$bestK}."
            );
        }

        // ── Jalankan klasifikasi untuk SEMUA pelanggan ────────────────────────
        $pelanggan = Pelanggan::all(); // ambil semua pelanggan dari database

        $count = 0; // counter pelanggan yang berhasil diklasifikasi
        foreach ($pelanggan as $p) {
            $hasil = $this->knnService->classify($p->id_pelanggan, $bestK);
            if ($hasil) $count++; // hitung yang berhasil
        }

        // Redirect ke halaman KNN dengan pesan sukses
        return redirect()->route('knn.index')
            ->with('success', "Seluruh pelanggan ($count data) telah berhasil diklasifikasikan ulang menggunakan K terbaik = {$bestK} (Akurasi: {$bestAcc}%).");
    }
}

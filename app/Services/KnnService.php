<?php

/**
 * KnnService.php — Layanan Klasifikasi K-Nearest Neighbor (KNN)
 *
 * Skripsi: Optimasi Web GIS Sistem Manajemen Jaringan WiFi Menggunakan KNN
 * Mahasiswa: Muhammad As'ad Muhibbin Akbar — NIM 220602077
 *
 * Implementasi ini sepenuhnya mengacu pada dokumen perhitungan Excel:
 * "perhitungan-knn-fix.xlsx" — Sheet1, Formula Kolom K (Euclidean Distance)
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * RUMUS UTAMA (Persamaan 3.1 Skripsi):
 *
 *   d(x,y) = √[ (Lat_i − Lat_uji)² + (Lon_i − Lon_uji)² +
 *               (Usage_i − Usage_uji)² + (Dev_i − Dev_uji)² ]
 *
 * Setara dengan formula Excel di Kolom K:
 *   =SQRT((C4-$C$108)^2 + (D4-$D$108)^2 + (E4-$E$108)^2 + (F4-$F$108)^2)
 *
 * PENTING: Operator yang digunakan adalah PENJUMLAHAN (+), BUKAN perkalian (*).
 * ─────────────────────────────────────────────────────────────────────────────
 */

namespace App\Services;

use App\Models\Pelanggan;
use App\Models\KnnParameter;
use App\Models\KnnHasil;
use App\Models\KnnDetailTetangga;

class KnnService
{
    /**
     * ─────────────────────────────────────────────────────────────────────────
     * FUNGSI 1: Menghitung Jarak Euclidean 4 Dimensi
     * ─────────────────────────────────────────────────────────────────────────
     *
     * 4 Dimensi / Atribut yang digunakan:
     *   1. Latitude  (Kolom C di Excel) — koordinat spasial lintang
     *   2. Longitude (Kolom D di Excel) — koordinat spasial bujur
     *   3. Usage_GB  (Kolom E di Excel) — total pemakaian kuota bulanan
     *   4. Jml_Device(Kolom F di Excel) — jumlah perangkat yang terhubung
     *
     * MENGAPA koordinat dikali 100.000 (10^5)?
     * → Koordinat desimal (mis. -7.13000) memiliki skala SANGAT KECIL
     *   dibandingkan Usage (250) dan Device (5).
     * → Jika tidak diskalakan, kontribusi spasial akan TENGGELAM (drowned out)
     *   dan tidak berpengaruh pada penentuan tetangga terdekat.
     * → Dengan dikali 10^5: -7.13000 → -713000, sehingga skalanya setara
     *   dengan nilai Usage dan Device dalam perhitungan jarak.
     *
     * Bukti dari Excel (Sheet1):
     *   Kolom C (Latitude) berisi -713000, bukan -7.13000
     *   Artinya nilai yang tersimpan di Excel sudah dalam bentuk scaled integer.
     *   Kita menyimpan dalam DB sebagai desimal (-7.13000) lalu konversi kembali.
     *
     * @param float $lat1   Latitude data target (desimal, mis. -7.13000)
     * @param float $lon1   Longitude data target (desimal, mis. 112.60000)
     * @param float $usage1 Usage GB data target
     * @param int   $dev1   Jumlah device data target
     * @param float $lat2   Latitude data pembanding/latih (desimal)
     * @param float $lon2   Longitude data pembanding/latih (desimal)
     * @param float $usage2 Usage GB data latih
     * @param int   $dev2   Jumlah device data latih
     * @return float Nilai jarak Euclidean 4D
     */
    public function euclideanDistance4D($lat1, $lon1, $usage1, $dev1, $lat2, $lon2, $usage2, $dev2)
    {
        // ── LANGKAH 1: Konversi koordinat desimal → integer scaled (× 10^5) ──────
        // Basis: koordinat di Excel disimpan sebagai integer (mis. -713000)
        // Di database Laravel disimpan sebagai desimal (mis. -7.13000)
        // Maka kita kalikan 100.000 untuk mendapatkan nilai yang ekuivalen dengan Excel
        // Contoh: -7.12976 × 100000 = -712976 (nilai Ruqoiyah di kolom C Excel)
        $scaledLat1 = (double)$lat1 * 100000; // Lat data uji/target → scaled
        $scaledLon1 = (double)$lon1 * 100000; // Lon data uji/target → scaled

        $scaledLat2 = (double)$lat2 * 100000; // Lat data latih → scaled
        $scaledLon2 = (double)$lon2 * 100000; // Lon data latih → scaled

        // ── LANGKAH 2: Hitung Euclidean Distance 4D ──────────────────────────────
        // Rumus: d = √[ (ΔLat)² + (ΔLon)² + (ΔUsage)² + (ΔDevice)² ]
        //
        // Sesuai formula Excel kolom K:
        // =SQRT((C4-$C$108)^2 + (D4-$D$108)^2 + (E4-$E$108)^2 + (F4-$F$108)^2)
        //
        // Contoh NYATA: Ruqoiyah (latih) vs Sari (uji)
        //   Lat : (-712976 − (-713000))² = (24)²    =    576
        //   Lon : (11260131 − 11260000)² = (131)²   = 17.161
        //   Usage: (272 − 250)²          = (22)²    =    484
        //   Device: (3 − 5)²             = (-2)²    =      4
        //                                  TOTAL    = 18.225
        //   √18225 = 135 ← sesuai kolom K di Excel
        return sqrt(
            pow($scaledLat1 - $scaledLat2, 2) + // (Lat_latih − Lat_uji)²
            pow($scaledLon1 - $scaledLon2, 2) + // (Lon_latih − Lon_uji)²
            pow((double)$usage1 - (double)$usage2, 2) + // (Usage_latih − Usage_uji)²
            pow((double)$dev1 - (double)$dev2, 2)        // (Device_latih − Device_uji)²
        );
        // INGAT: Operator PENJUMLAHAN (+), bukan perkalian (*)!
        // Formula SALAH: =SQRT((C4-$C$108)^2 * (D4-$D$108)^2 * ...) ← JANGAN PAKAI INI
    }

    /**
     * ─────────────────────────────────────────────────────────────────────────
     * FUNGSI 2: Klasifikasi Pelanggan menggunakan KNN
     * ─────────────────────────────────────────────────────────────────────────
     *
     * Alur kerja (sesuai Tabel 8 di Bab 3 Skripsi):
     *   1. Ambil data pelanggan target (data uji)
     *   2. Ambil semua data latih (pelanggan yang sudah memiliki label)
     *   3. Hitung jarak Euclidean 4D ke setiap data latih → Kolom K Excel
     *   4. Urutkan jarak dari terkecil → Kolom L (RANK) Excel
     *   5. Ambil K tetangga terdekat (K=3 default) → Kolom M (IF KNN) Excel
     *   6. Voting label mayoritas → Kolom N (Hasil Prediksi) Excel
     *   7. Simpan hasil ke database dan update label pelanggan
     *
     * @param int $targetPelangganId ID pelanggan yang akan diklasifikasikan
     * @param int $k                 Nilai K (jumlah tetangga terdekat, default=3)
     * @return KnnHasil|null         Object hasil klasifikasi, atau null jika gagal
     */
    public function classify($targetPelangganId, $k = 3)
    {
        // ── LANGKAH 1: Ambil data pelanggan yang akan diprediksi (DATA UJI) ──────
        // Setara dengan baris 108 di Excel (baris khusus data uji Sari)
        $target = Pelanggan::findOrFail($targetPelangganId);

        // ── LANGKAH 2: Ambil semua DATA LATIH dari database ──────────────────────
        // Data latih = pelanggan yang sudah memiliki label prioritas (HIGH/MEDIUM/LOW)
        // Setara dengan baris 4–103 di Excel (100 baris data latih)
        // Kita kecualikan data target sendiri agar tidak dihitung jaraknya ke diri sendiri
        $trainingSet = Pelanggan::whereNotNull('prioritas_label') // hanya yang sudah berlabel
            ->where('id_pelanggan', '!=', $targetPelangganId)    // kecualikan diri sendiri
            ->get();

        // ── Validasi: pastikan data latih mencukupi untuk K tetangga ─────────────
        if ($trainingSet->count() < $k) {
            return null; // Tidak cukup data latih → klasifikasi tidak bisa dilakukan
        }

        // ── LANGKAH 3: Hitung jarak Euclidean ke setiap data latih ───────────────
        // Setara dengan mengisi formula kolom K di Excel untuk setiap baris latih
        $distances = [];
        foreach ($trainingSet as $train) {
            // Panggil fungsi Euclidean 4D untuk setiap pasang (target, latih)
            $dist = $this->euclideanDistance4D(
                $target->latitude,  $target->longitude,  $target->usage_gb,  $target->jumlah_device,  // DATA UJI ($C$108)
                $train->latitude,   $train->longitude,   $train->usage_gb,   $train->jumlah_device     // DATA LATIH (C4, D4, E4, F4)
            );

            // Simpan hasil jarak beserta info data latih
            $distances[] = [
                'id_pelanggan' => $train->id_pelanggan,
                'distance'     => $dist,             // Nilai kolom K di Excel
                'label'        => $train->prioritas_label, // Nilai kolom G di Excel (label aktual)
                'nama'         => $train->nama_pelanggan
            ];
        }

        // ── LANGKAH 4: Urutkan jarak dari terkecil ke terbesar (RANK) ────────────
        // Setara dengan formula kolom L: =RANK(K4, $K$4:$K$103, 1)
        // Argumen 1 = ascending: Rank 1 = jarak terkecil = paling mirip dengan data uji
        usort($distances, function($a, $b) {
            return $a['distance'] <=> $b['distance']; // sorting ascending (terkecil dulu)
        });

        // ── LANGKAH 5: Ambil K tetangga terdekat (default K=3) ───────────────────
        // Setara dengan kolom M: =IF(L4<=3, G4, "")
        // Hanya baris dengan RANK ≤ K yang ditampilkan labelnya, sisanya kosong
        $neighbors = array_slice($distances, 0, $k); // ambil K elemen pertama (terkecil)

        // ── LANGKAH 6: Voting Label — tentukan label mayoritas dari K tetangga ────
        // Setara dengan kolom N: hasil prediksi = label yang paling sering muncul
        // Contoh: Ruqoiyah=MEDIUM, Afi=MEDIUM, c_duwan=MEDIUM → MEDIUM (3-0-0)
        $votes = []; // akumulator suara per label
        foreach ($neighbors as $n) {
            $label = strtoupper($n['label']); // normalisasi ke huruf besar

            // ── Standardisasi label agar sesuai format skripsi ───────────────────
            // Beberapa data mungkin menggunakan nama label yang berbeda
            if ($label === 'SANGAT PRIORITAS' || $label === 'HIGH') {
                $label = 'HIGH';   // prioritas tinggi (pelanggan bermasalah berat)
            } elseif ($label === 'PRIORITAS' || $label === 'MEDIUM') {
                $label = 'MEDIUM'; // prioritas sedang
            } else {
                $label = 'LOW';    // prioritas rendah (pelanggan normal/stabil)
            }

            $votes[$label] = ($votes[$label] ?? 0) + 1; // tambah 1 suara untuk label ini
        }

        // Urutkan votes dari terbanyak ke tersedikit
        arsort($votes); // descending sort by value
        $resultLabel = key($votes); // ambil label dengan suara terbanyak = HASIL PREDIKSI

        // ── LANGKAH 7: Simpan parameter KNN ke database ──────────────────────────
        // Mencatat nilai K dan metrik jarak yang digunakan
        $param = KnnParameter::create([
            'nilai_k'         => $k,                             // Nilai K yang dipakai (default: 3)
            'distance_metric' => 'Euclidean (4D) - Thesis Match' // Metode sesuai skripsi
        ]);

        // ── LANGKAH 8: Simpan hasil klasifikasi utama ────────────────────────────
        $hasil = KnnHasil::create([
            'id_pelanggan'  => $targetPelangganId,        // ID pelanggan yang diklasifikasi
            'id_knn_param'  => $param->id_knn_param,      // Referensi ke parameter KNN
            'jarak_min'     => $neighbors[0]['distance'], // Jarak ke tetangga terdekat (Rank 1)
            'label_hasil'   => $resultLabel               // Label hasil voting mayoritas
        ]);

        // ── LANGKAH 9: Simpan detail K tetangga terdekat ─────────────────────────
        // Ini yang ditampilkan di tabel UI sidang (Tabel 4.5 skripsi)
        foreach ($neighbors as $index => $n) {
            // Standardisasi label tetangga
            $neighborLabel = strtoupper($n['label']);
            if ($neighborLabel === 'SANGAT PRIORITAS' || $neighborLabel === 'HIGH') {
                $neighborLabel = 'HIGH';
            } elseif ($neighborLabel === 'PRIORITAS' || $neighborLabel === 'MEDIUM') {
                $neighborLabel = 'MEDIUM';
            } else {
                $neighborLabel = 'LOW';
            }

            KnnDetailTetangga::create([
                'id_knn_hasil'         => $hasil->id_knn_hasil,    // FK ke hasil utama
                'urutan'               => $index + 1,              // Rank (1 = terdekat)
                'id_pelanggan_tetangga'=> $n['id_pelanggan'],      // ID data latih tetangga
                'jarak_euclidean'      => $n['distance'],          // Nilai jarak Euclidean (= Kolom K Excel)
                'label_tetangga'       => $neighborLabel           // Label data latih (= Kolom G Excel)
            ]);
        }

        // ── LANGKAH 10: Update label pelanggan di tabel utama ────────────────────
        // Setelah diklasifikasikan, label prioritas pelanggan diperbarui
        // HIGH   = perlu kunjungan teknisi SEGERA (gangguan berat)
        // MEDIUM = perlu perhatian terjadwal
        // LOW    = pelanggan stabil, monitoring rutin
        $target->update(['prioritas_label' => $resultLabel]);

        return $hasil; // Kembalikan object hasil untuk ditampilkan di UI
    }

    /**
     * ─────────────────────────────────────────────────────────────────────────
     * FUNGSI 3: Evaluasi Akurasi untuk Nilai K Tertentu (TANPA menyimpan ke DB)
     * ─────────────────────────────────────────────────────────────────────────
     *
     * Menggunakan strategi Leave-One-Out (LOO): setiap data berlabel diprediksi
     * menggunakan data berlabel lainnya sebagai training set, lalu dibandingkan
     * dengan label aktualnya untuk menghitung akurasi.
     *
     * @param int $k Nilai K yang akan dievaluasi
     * @return array ['k' => int, 'benar' => int, 'total' => int, 'akurasi' => float]
     */
    public function evaluateAccuracy(int $k): array
    {
        // Ambil semua data yang sudah memiliki label aktual (data berlabel)
        $labeledData = Pelanggan::whereNotNull('prioritas_label')->get();
        $total  = $labeledData->count();
        $benar  = 0;

        if ($total < $k + 1) {
            // Tidak cukup data untuk dievaluasi dengan K ini
            return ['k' => $k, 'benar' => 0, 'total' => $total, 'akurasi' => 0.0];
        }

        foreach ($labeledData as $testItem) {
            // Training set = semua data berlabel KECUALI data uji saat ini (Leave-One-Out)
            $trainingSet = $labeledData->filter(fn($p) => $p->id_pelanggan !== $testItem->id_pelanggan);

            if ($trainingSet->count() < $k) {
                continue; // skip jika tidak cukup tetangga
            }

            // ── Hitung jarak ke semua data latih ─────────────────────────────
            $distances = [];
            foreach ($trainingSet as $train) {
                $dist = $this->euclideanDistance4D(
                    $testItem->latitude, $testItem->longitude, $testItem->usage_gb, $testItem->jumlah_device,
                    $train->latitude,    $train->longitude,    $train->usage_gb,    $train->jumlah_device
                );
                $distances[] = [
                    'distance' => $dist,
                    'label'    => $train->prioritas_label,
                ];
            }

            // ── Urutkan ascending, ambil K terdekat ──────────────────────────
            usort($distances, fn($a, $b) => $a['distance'] <=> $b['distance']);
            $neighbors = array_slice($distances, 0, $k);

            // ── Voting mayoritas ──────────────────────────────────────────────
            $votes = [];
            foreach ($neighbors as $n) {
                $label = strtoupper($n['label']);
                if ($label === 'SANGAT PRIORITAS' || $label === 'HIGH')   { $label = 'HIGH'; }
                elseif ($label === 'PRIORITAS' || $label === 'MEDIUM')    { $label = 'MEDIUM'; }
                else                                                       { $label = 'LOW'; }
                $votes[$label] = ($votes[$label] ?? 0) + 1;
            }
            arsort($votes);
            $predicted = key($votes);

            // ── Bandingkan dengan label aktual ────────────────────────────────
            $actual = strtoupper($testItem->prioritas_label);
            if ($actual === 'SANGAT PRIORITAS' || $actual === 'HIGH')  { $actual = 'HIGH'; }
            elseif ($actual === 'PRIORITAS' || $actual === 'MEDIUM')   { $actual = 'MEDIUM'; }
            else                                                        { $actual = 'LOW'; }

            if ($predicted === $actual) {
                $benar++;
            }
        }

        $akurasi = $total > 0 ? round(($benar / $total) * 100, 2) : 0.0;

        return [
            'k'       => $k,
            'benar'   => $benar,
            'salah'   => $total - $benar,
            'total'   => $total,
            'akurasi' => $akurasi,
        ];
    }

    /**
     * ─────────────────────────────────────────────────────────────────────────
     * FUNGSI 4: Temukan K Terbaik (Loop K = 1 sampai 9)
     * ─────────────────────────────────────────────────────────────────────────
     *
     * Menjalankan evaluasi akurasi untuk setiap K dari 1 hingga 9,
     * lalu mengembalikan K dengan akurasi tertinggi beserta ringkasan semua K.
     *
     * Jika ada beberapa K dengan akurasi sama, dipilih K terkecil (lebih sederhana).
     *
     * @return array [
     *   'best_k'    => int,           // K optimal yang dipilih
     *   'best_acc'  => float,         // Akurasi K optimal (%)
     *   'all'       => array,         // Hasil evaluasi semua K (array of array)
     * ]
     */
    public function findBestK(): array
    {
        $results = [];
        $bestK   = 1;
        $bestAcc = -1;

        for ($k = 1; $k <= 9; $k++) {
            $eval = $this->evaluateAccuracy($k);
            $results[] = $eval;

            // Pilih K terkecil jika akurasi sama (prinsip Occam's Razor)
            if ($eval['akurasi'] > $bestAcc) {
                $bestAcc = $eval['akurasi'];
                $bestK   = $k;
            }
        }

        return [
            'best_k'   => $bestK,
            'best_acc' => $bestAcc,
            'all'      => $results,
        ];
    }
}

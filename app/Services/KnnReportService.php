<?php

namespace App\Services;

use App\Models\Pelanggan;

class KnnReportService
{
    protected $knnService;

    public function __construct(KnnService $knnService)
    {
        $this->knnService = $knnService;
    }

    /**
     * Membagi dataset menjadi 75 data latih dan 25 data uji
     */
    protected function getSplitDataset()
    {
        $allPelanggan = Pelanggan::whereNotNull('prioritas_label')
            ->orderBy('id_pelanggan', 'asc')
            ->get();

        // 75 data pertama sebagai training set
        $trainingSet = $allPelanggan->slice(0, 75);
        // 25 data berikutnya sebagai test set
        $testSet = $allPelanggan->slice(75, 25);

        return [$trainingSet, $testSet];
    }

    /**
     * Melakukan prediksi label pelanggan menggunakan training set
     */
    protected function predict(Pelanggan $target, $trainingSet, $k)
    {
        $distances = [];
        foreach ($trainingSet as $train) {
            $dist = $this->knnService->euclideanDistance4D(
                $target->latitude,  $target->longitude,  $target->usage_gb,  $target->jumlah_device,
                $train->latitude,   $train->longitude,   $train->usage_gb,   $train->jumlah_device
            );

            $distances[] = [
                'label'    => $train->prioritas_label,
                'distance' => $dist
            ];
        }

        // Urutkan jarak terkecil ke terbesar
        usort($distances, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        // Ambil K tetangga terdekat
        $neighbors = array_slice($distances, 0, $k);

        // Voting label
        $votes = [];
        foreach ($neighbors as $n) {
            $label = strtoupper($n['label']);
            if ($label === 'SANGAT PRIORITAS' || $label === 'HIGH') {
                $label = 'HIGH';
            } elseif ($label === 'PRIORITAS' || $label === 'MEDIUM') {
                $label = 'MEDIUM';
            } else {
                $label = 'LOW';
            }
            $votes[$label] = ($votes[$label] ?? 0) + 1;
        }

        arsort($votes);
        return key($votes) ?: 'LOW';
    }

    /**
     * Tabel 4.1 Perbandingan Hasil Aktual vs Prediksi KNN (K=3)
     */
    public function getPerbandinganK3(): array
    {
        list($trainingSet, $testSet) = $this->getSplitDataset();
        $results = [];

        foreach ($testSet as $p) {
            $predicted = $this->predict($p, $trainingSet, 3);
            
            // Normalisasi label aktual
            $actual = strtoupper($p->prioritas_label);
            if ($actual === 'SANGAT PRIORITAS' || $actual === 'HIGH') {
                $actual = 'HIGH';
            } elseif ($actual === 'PRIORITAS' || $actual === 'MEDIUM') {
                $actual = 'MEDIUM';
            } else {
                $actual = 'LOW';
            }

            $results[] = [
                'id_pelanggan'   => $p->id_pelanggan,
                'nama_pelanggan' => $p->nama_pelanggan,
                'label_aktual'   => $actual,
                'label_prediksi' => $predicted,
                'status'         => ($actual === $predicted) ? 'BENAR' : 'SALAH',
            ];
        }

        return $results;
    }

    /**
     * Tabel 4.2 Evaluasi Akurasi Sistem Berdasarkan Nilai K (K=1 s/d 9)
     */
    public function getEvaluasiAkurasiSemuaK(): array
    {
        list($trainingSet, $testSet) = $this->getSplitDataset();
        $results = [];

        for ($k = 1; $k <= 9; $k++) {
            $benar = 0;
            foreach ($testSet as $p) {
                $predicted = $this->predict($p, $trainingSet, $k);

                $actual = strtoupper($p->prioritas_label);
                if ($actual === 'SANGAT PRIORITAS' || $actual === 'HIGH') {
                    $actual = 'HIGH';
                } elseif ($actual === 'PRIORITAS' || $actual === 'MEDIUM') {
                    $actual = 'MEDIUM';
                } else {
                    $actual = 'LOW';
                }

                if ($actual === $predicted) {
                    $benar++;
                }
            }

            $total = $testSet->count();
            $salah = $total - $benar;
            $results[] = [
                'nilai_k'        => $k,
                'total_uji'      => $total,
                'jumlah_benar'   => $benar,
                'jumlah_salah'   => $salah,
                'akurasi_persen' => $total > 0 ? round(($benar / $total) * 100, 2) : 0,
            ];
        }

        return $results;
    }

    /**
     * Tabel 4.3 Classification Report K=3
     */
    public function getClassificationReportK3(): array
    {
        list($trainingSet, $testSet) = $this->getSplitDataset();

        $classes = ['HIGH', 'MEDIUM', 'LOW'];
        $stats = [];
        foreach ($classes as $c) {
            $stats[$c] = [
                'tp' => 0,
                'fp' => 0,
                'fn' => 0,
                'support' => 0
            ];
        }

        foreach ($testSet as $p) {
            $predicted = $this->predict($p, $trainingSet, 3);

            $actual = strtoupper($p->prioritas_label);
            if ($actual === 'SANGAT PRIORITAS' || $actual === 'HIGH') {
                $actual = 'HIGH';
            } elseif ($actual === 'PRIORITAS' || $actual === 'MEDIUM') {
                $actual = 'MEDIUM';
            } else {
                $actual = 'LOW';
            }

            // Hitung support untuk actual label
            if (isset($stats[$actual])) {
                $stats[$actual]['support']++;
            }

            if ($predicted === $actual) {
                $stats[$actual]['tp']++;
            } else {
                // Prediksi salah
                // predicted adalah FP untuk kelas predicted
                if (isset($stats[$predicted])) {
                    $stats[$predicted]['fp']++;
                }
                // actual adalah FN untuk kelas actual
                if (isset($stats[$actual])) {
                    $stats[$actual]['fn']++;
                }
            }
        }

        $report = [];
        $totalCorrect = 0;
        $totalSupport = $testSet->count();

        $macroPrecisionSum = 0;
        $macroRecallSum = 0;
        $macroF1Sum = 0;

        foreach ($classes as $c) {
            $tp = $stats[$c]['tp'];
            $fp = $stats[$c]['fp'];
            $fn = $stats[$c]['fn'];
            $support = $stats[$c]['support'];

            $totalCorrect += $tp;

            $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0;
            $recall = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0;
            $f1 = ($precision + $recall) > 0 ? 2 * ($precision * $recall) / ($precision + $recall) : 0;

            $macroPrecisionSum += $precision;
            $macroRecallSum += $recall;
            $macroF1Sum += $f1;

            $report[$c] = [
                'precision' => round($precision, 2),
                'recall'    => round($recall, 2),
                'f1_score'  => round($f1, 2),
                'support'   => $support,
            ];
        }

        $report['macro_avg'] = [
            'precision' => round($macroPrecisionSum / 3, 2),
            'recall'    => round($macroRecallSum / 3, 2),
            'f1_score'  => round($macroF1Sum / 3, 2),
        ];

        $report['accuracy'] = $totalSupport > 0 ? round(($totalCorrect / $totalSupport) * 100, 2) : 0;

        return $report;
    }
}

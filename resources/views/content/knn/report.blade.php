@extends('layouts/contentNavbarLayout')

@section('title', 'Laporan Sidang KNN - Bab IV')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Analisis /</span> Laporan Hasil Pengujian KNN (Bab IV)</h4>

<!-- Info Dataset Card -->
<div class="card mb-4 shadow-sm border-left-primary">
  <div class="card-body">
    <h5 class="card-title fw-bold text-primary"><i class="bx bx-info-circle me-1"></i> Parameter Eksperimen Pengujian</h5>
    <p class="card-text mb-0">
      Berdasarkan metodologi pada skripsi, dataset pelanggan dibagi menjadi <strong>100 Data Pelanggan</strong>:
    </p>
    <ul>
      <li><strong>75 Data Latih (Training Set)</strong>: Digunakan sebagai basis pencarian tetangga terdekat.</li>
      <li><strong>25 Data Uji (Test Set)</strong>: Digunakan untuk menguji keakuratan klasifikasi.</li>
      <li><strong>Nilai K Optimal</strong>: Eksperimen menunjukkan <strong>K = 3</strong> memberikan hasil terbaik.</li>
    </ul>
  </div>
</div>

<div class="row">
  <!-- Tabel 4.1 Perbandingan Hasil Aktual vs Prediksi KNN (K=3) -->
  <div class="col-md-12 mb-4">
    <div class="card shadow-sm border-left-primary">
      <div class="card-header bg-label-primary py-3">
        <h5 class="mb-0 fw-bold text-primary"><i class="bx bx-table me-2"></i> Tabel 4.1 Perbandingan Hasil Aktual vs Prediksi KNN (K=3)</h5>
      </div>
      <div class="card-body pt-3">
        <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
          <table class="table table-bordered table-hover table-striped">
            <thead>
              <tr class="bg-light">
                <th class="text-center" style="width: 50px;">No</th>
                <th class="text-center">ID Pelanggan</th>
                <th>Nama Pelanggan</th>
                <th class="text-center">Label Aktual</th>
                <th class="text-center">Label Prediksi</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($perbandinganK3 as $index => $row)
              <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center"><code>KTR{{ str_pad($row['id_pelanggan'], 2, '0', STR_PAD_LEFT) }}</code></td>
                <td><strong>{{ $row['nama_pelanggan'] }}</strong></td>
                <td class="text-center">
                  @if($row['label_aktual'] == 'HIGH')
                    <span class="badge bg-label-danger">HIGH</span>
                  @elseif($row['label_aktual'] == 'MEDIUM')
                    <span class="badge bg-label-warning text-dark">MEDIUM</span>
                  @else
                    <span class="badge bg-label-success">LOW</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($row['label_prediksi'] == 'HIGH')
                    <span class="badge bg-danger">HIGH</span>
                  @elseif($row['label_prediksi'] == 'MEDIUM')
                    <span class="badge bg-warning text-dark">MEDIUM</span>
                  @else
                    <span class="badge bg-success">LOW</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($row['status'] == 'BENAR')
                    <span class="badge bg-success"><i class="bx bx-check me-1"></i>BENAR</span>
                  @else
                    <span class="badge bg-danger"><i class="bx bx-x me-1"></i>SALAH</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabel 4.2 Evaluasi Akurasi Sistem Berdasarkan Nilai K -->
  <div class="col-md-6 mb-4">
    <div class="card shadow-sm border-left-info h-100">
      <div class="card-header bg-label-info py-3">
        <h5 class="mb-0 fw-bold text-info"><i class="bx bx-chart me-2"></i> Tabel 4.2 Evaluasi Akurasi Sistem Berdasarkan Nilai K</h5>
      </div>
      <div class="card-body pt-3">
        <div class="table-responsive">
          <table class="table table-bordered table-hover text-center">
            <thead>
              <tr class="bg-light">
                <th class="text-center">No</th>
                <th class="text-center">Nilai K</th>
                <th class="text-center">Data Uji</th>
                <th class="text-center">Prediksi Benar</th>
                <th class="text-center">Prediksi Salah</th>
                <th class="text-center">Akurasi (%)</th>
              </tr>
            </thead>
            <tbody>
              @foreach($evaluasiK as $index => $row)
              <tr class="{{ $row['nilai_k'] == 3 ? 'table-success fw-bold text-info' : '' }}" @if($row['nilai_k'] == 3) style="border: 2px solid #03c3ec;" @endif>
                <td>{{ $index + 1 }}</td>
                <td>K = {{ $row['nilai_k'] }} @if($row['nilai_k'] == 3) (Optimal) @endif</td>
                <td>{{ $row['total_uji'] }}</td>
                <td>{{ $row['jumlah_benar'] }}</td>
                <td>{{ $row['jumlah_salah'] }}</td>
                <td>{{ $row['akurasi_persen'] }}%</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabel 4.3 Classification Report K=3 -->
  <div class="col-md-6 mb-4">
    <div class="card shadow-sm border-left-success h-100">
      <div class="card-header bg-label-success py-3">
        <h5 class="mb-0 fw-bold text-success"><i class="bx bx-bar-chart-alt me-2"></i> Tabel 4.3 Classification Report K=3</h5>
      </div>
      <div class="card-body pt-3">
        <div class="table-responsive">
          <table class="table table-bordered table-hover text-center">
            <thead>
              <tr class="bg-light">
                <th>Kelas</th>
                <th>Precision</th>
                <th>Recall</th>
                <th>F1-Score</th>
                <th>Support</th>
              </tr>
            </thead>
            <tbody>
              @foreach(['HIGH', 'MEDIUM', 'LOW'] as $c)
              <tr>
                <td><strong>{{ $c }}</strong></td>
                <td>{{ number_format($classificationK3[$c]['precision'], 2) }}</td>
                <td>{{ number_format($classificationK3[$c]['recall'], 2) }}</td>
                <td>{{ number_format($classificationK3[$c]['f1_score'], 2) }}</td>
                <td>{{ $classificationK3[$c]['support'] }}</td>
              </tr>
              @endforeach
              <tr class="table-light fw-bold" style="border-top: 2px solid #ddd;">
                <td>Accuracy</td>
                <td colspan="3" class="text-end text-primary">{{ number_format($classificationK3['accuracy'], 2) }}%</td>
                <td>{{ $perbandinganK3 ? count($perbandinganK3) : 0 }}</td>
              </tr>
              <tr class="table-light text-muted">
                <td>Macro Avg</td>
                <td>{{ number_format($classificationK3['macro_avg']['precision'], 2) }}</td>
                <td>{{ number_format($classificationK3['macro_avg']['recall'], 2) }}</td>
                <td>{{ number_format($classificationK3['macro_avg']['f1_score'], 2) }}</td>
                <td>{{ $perbandinganK3 ? count($perbandinganK3) : 0 }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

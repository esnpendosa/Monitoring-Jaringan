<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tutorial;
use App\Models\User;

class TutorialSeeder extends Seeder
{
    public function run(): void
    {
        // Cari user admin pertama untuk dikaitkan sebagai pembuat
        $admin = User::whereHas('role', function($q) {
            $q->whereIn('name', ['Admin', 'Manajer']);
        })->first() ?? User::first();

        $adminId = $admin ? $admin->id : null;

        $data = [
            [
                'judul' => 'Cara Mengganti Nama WiFi & Password Modem Huawei HG8245H5',
                'slug' => 'cara-ganti-nama-wifi-password-huawei-hg8245h5',
                'kategori' => 'Modem',
                'ringkasan' => 'Panduan lengkap dan praktis cara mengganti nama jaringan WiFi (SSID) dan kata sandi pada modem ONT Huawei HG8245H5.',
                'konten' => '
                    <p>Mengganti password WiFi secara berkala sangat disarankan untuk menjaga keamanan jaringan internet rumah Anda dari pengguna yang tidak dikenal. Berikut adalah langkah-langkah mengganti nama WiFi dan password pada modem <strong>Huawei HG8245H5</strong>:</p>
                    
                    <h3 class="fw-bold mt-4">Langkah 1: Hubungkan Perangkat ke Modem</h3>
                    <p>Pastikan HP atau Laptop Anda sudah terhubung ke jaringan WiFi modem Huawei tersebut (atau menggunakan kabel LAN jika menggunakan PC).</p>
                    
                    <h3 class="fw-bold mt-4">Langkah 2: Buka IP Address Modem di Browser</h3>
                    <ol>
                        <li>Buka browser favorit Anda (Google Chrome, Safari, atau Firefox).</li>
                        <li>Ketik IP address modem pada kolom URL: <strong class="text-primary">192.168.100.1</strong> lalu tekan <strong>Enter</strong>.</li>
                    </ol>

                    <h3 class="fw-bold mt-4">Langkah 3: Login ke Web Admin</h3>
                    <p>Masukkan username dan password administrator berikut (pilih salah satu jika gagal):</p>
                    <table class="table table-bordered mt-2 mb-3">
                        <thead class="table-light">
                            <tr>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Level Akses</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>telecomadmin</code></td>
                                <td><code>admintelecom</code></td>
                                <td>Super Admin (Direkomendasikan)</td>
                            </tr>
                            <tr>
                                <td><code>admin</code></td>
                                <td><code>admin</code></td>
                                <td>Standard User</td>
                            </tr>
                        </tbody>
                    </table>

                    <h3 class="fw-bold mt-4">Langkah 4: Ubah Nama WiFi & Password</h3>
                    <ol>
                        <li>Setelah berhasil login, klik menu <strong>Advanced</strong> di bagian atas.</li>
                        <li>Pilih sub-menu <strong>WLAN</strong> &gt; <strong>WLAN Basic Configuration</strong> di sisi kiri.</li>
                        <li>Untuk mengubah nama WiFi, ganti isi kolom <strong>SSID Name</strong> sesuai keinginan Anda.</li>
                        <li>Untuk mengubah password WiFi, ganti isi kolom <strong>WPA PreSharedKey</strong> dengan password baru Anda (minimal 8 karakter).</li>
                        <li>Klik tombol <strong>Apply</strong> untuk menyimpan perubahan.</li>
                    </ol>

                    <blockquote class="bg-light p-3 border-start border-primary border-4 rounded mt-4">
                        <strong>Catatan Penting:</strong> Setelah Anda menekan tombol Apply, koneksi WiFi di HP atau laptop Anda akan otomatis terputus. Silakan hubungkan ulang dengan memilih nama WiFi baru dan memasukkan password yang baru saja Anda buat.
                    </blockquote>
                ',
                'urutan' => 1,
                'is_published' => true,
                'created_by' => $adminId,
            ],
            [
                'judul' => 'Cara Membatasi Jumlah Pengguna WiFi pada Modem Huawei',
                'slug' => 'cara-membatasi-jumlah-pengguna-wifi-huawei',
                'kategori' => 'WiFi',
                'ringkasan' => 'Batasi kuota perangkat yang terhubung ke modem Huawei Anda untuk menghindari penurunan kecepatan akibat kelebihan pengguna.',
                'konten' => '
                    <p>Apakah internet Anda terasa lambat? Bisa jadi terlalu banyak perangkat yang terhubung secara bersamaan. Untuk menjaga kualitas internet tetap stabil, Anda bisa membatasi jumlah maksimal pengguna (HP/Laptop) yang boleh tersambung ke WiFi.</p>
                    
                    <h3 class="fw-bold mt-4">Langkah Pembatasan Pengguna WiFi:</h3>
                    <ol>
                        <li>Sambungkan perangkat Anda ke jaringan WiFi, lalu buka halaman admin modem melalui URL <strong class="text-primary">192.168.100.1</strong>.</li>
                        <li>Login menggunakan username: <code>telecomadmin</code> dan password: <code>admintelecom</code>.</li>
                        <li>Masuk ke menu <strong>Advanced</strong> &gt; <strong>WLAN</strong> &gt; <strong>WLAN Basic Configuration</strong>.</li>
                        <li>Cari kolom bernama <strong>Number of Associated Devices</strong>.</li>
                        <li>Ubah nilainya dari default (misal 32) menjadi jumlah yang Anda inginkan (misal: <code>8</code> jika Anda hanya ingin maksimal 8 perangkat yang terhubung).</li>
                        <li>Klik <strong>Apply</strong> untuk menyimpan pengaturan.</li>
                    </ol>
                    
                    <h3 class="fw-bold mt-4">Mengapa Fitur ini Berguna?</h3>
                    <ul>
                        <li><strong>Mencegah Pencurian WiFi:</strong> Meskipun orang lain mengetahui password Anda, mereka tidak akan bisa masuk apabila kuota batas maksimal perangkat sudah terpenuhi.</li>
                        <li><strong>Bandwidth Stabil:</strong> Menghindari lag saat bermain game online atau streaming video akibat jaringan yang terbagi terlalu banyak.</li>
                    </ul>
                ',
                'urutan' => 2,
                'is_published' => true,
                'created_by' => $adminId,
            ],
            [
                'judul' => 'Cara Mengatasi Lampu LOS Merah Berkedip di Modem Huawei',
                'slug' => 'cara-mengatasi-los-merah-modem-huawei',
                'kategori' => 'Troubleshooting',
                'ringkasan' => 'Panduan cepat mendiagnosis dan menangani kendala lampu indikator LOS menyala merah berkedip pada modem Huawei Anda.',
                'konten' => '
                    <p>Lampu indikator <strong>LOS (Loss of Signal)</strong> yang berkedip merah pada modem Huawei menandakan bahwa modem Anda tidak menerima sinyal optik (cahaya) dari jalur kabel fiber optik utama. Berikut cara mendiagnosis dan solusinya:</p>
                    
                    <h3 class="fw-bold mt-4">Langkah Awal Pengecekan Mandiri</h3>
                    <ol>
                        <li>
                            <strong>Periksa Konektor Kabel Kuning (Patch Cord):</strong>
                            <p>Lihat bagian bawah atau belakang modem Anda. Pastikan konektor kabel fiber berwarna biru/hijau yang menancap ke port bertuliskan <strong>PON</strong> sudah terpasang dengan kencang dan tidak longgar.</p>
                        </li>
                        <li>
                            <strong>Periksa Tekukan Kabel:</strong>
                            <p>Kabel fiber optik terbuat dari kaca tipis. Pastikan kabel tidak tertekuk tajam, terjepit pintu, atau tergulung terlalu sempit karena hal ini dapat memutus aliran cahaya di dalam kabel.</p>
                        </li>
                        <li>
                            <strong>Restart Modem:</strong>
                            <p>Matikan modem menggunakan tombol power di sisi samping, tunggu sekitar 30 detik, kemudian nyalakan kembali.</p>
                        </li>
                    </ol>

                    <h3 class="fw-bold mt-4">Kapan Harus Menghubungi Admin Rozitech?</h3>
                    <p>Jika Anda sudah mencoba langkah di atas namun lampu LOS masih tetap berkedip merah, kemungkinan terjadi:</p>
                    <ul>
                        <li>Kabel fiber optik di luar rumah putus akibat terkena pohon atau gesekan.</li>
                        <li>Ada gangguan massal di area Anda.</li>
                        <li>Port splitter di tiang ODP mengalami masalah teknis.</li>
                    </ul>
                    
                    <blockquote class="bg-light p-3 border-start border-danger border-4 rounded mt-4">
                        <strong>Solusi Tercepat:</strong> Silakan buat <strong>Tiket Gangguan</strong> melalui menu di dashboard portal ini agar teknisi kami dapat langsung diterjunkan ke lokasi Anda untuk melakukan perbaikan.
                    </blockquote>
                ',
                'urutan' => 3,
                'is_published' => true,
                'created_by' => $adminId,
            ],
            [
                'judul' => 'Cara Mengganti Nama WiFi & Password Modem HUAWEI EchoLife EG8141H5',
                'slug' => 'cara-ganti-nama-wifi-password-huawei-echolife-eg8141h5',
                'kategori' => 'Modem',
                'ringkasan' => 'Panduan lengkap cara mengubah SSID (nama WiFi) dan kata sandi pada modem GPON ONT HUAWEI EchoLife EG8141H5 melalui IP 192.168.18.1.',
                'konten' => '
                    <p>Mengubah SSID (Nama WiFi) dan Password secara berkala pada modem <strong>HUAWEI EchoLife EG8141H5</strong> akan mengamankan jaringan Anda dari akses tidak sah. Berikut langkah-langkahnya:</p>
                    
                    <h3 class="fw-bold mt-4">Langkah 1: Hubungkan Perangkat ke Modem</h3>
                    <p>Sambungkan HP atau laptop Anda ke WiFi modem Huawei Anda, atau hubungkan PC menggunakan kabel LAN ke salah satu port LAN modem.</p>
                    
                    <h3 class="fw-bold mt-4">Langkah 2: Buka IP Address di Browser</h3>
                    <p>Buka Chrome, Safari, atau Firefox, lalu ketik IP Default: <strong class="text-primary">192.168.18.1</strong> dan tekan Enter.</p>
                    
                    <h3 class="fw-bold mt-4">Langkah 3: Login Web Admin</h3>
                    <p>Masukkan salah satu kredensial administrator berikut:</p>
                    <ul>
                        <li>Username: <code>telecomadmin</code> | Password: <code>admintelecom</code> (Superadmin - Direkomendasikan)</li>
                        <li>Username: <code>deviceadmin</code> | Password: <code>aNu5uH5</code></li>
                    </ul>
                    
                    <h3 class="fw-bold mt-4">Langkah 4: Konfigurasi WLAN</h3>
                    <ol>
                        <li>Klik menu <strong>Advanced</strong> atau <strong>Advanced Configuration</strong> di menu atas.</li>
                        <li>Pilih menu <strong>WLAN</strong> pada panel kiri, lalu pilih <strong>WLAN Basic Configuration</strong>.</li>
                        <li>Pada bagian <strong>SSID Name</strong>, ganti nama WiFi lama dengan nama baru Anda.</li>
                        <li>Centang opsi <strong>Enable SSID</strong>.</li>
                        <li>Pada bagian <strong>WPA PreSharedKey</strong>, masukkan kata sandi baru Anda (minimal 8 karakter).</li>
                        <li>Klik <strong>Apply</strong> di bagian bawah untuk menyimpan perubahan.</li>
                    </ol>
                    <blockquote class="bg-light p-3 border-start border-primary border-4 rounded mt-4">
                        <strong>Catatan:</strong> Koneksi WiFi Anda akan terputus sesaat setelah mengklik Apply. Sambungkan kembali perangkat Anda menggunakan Nama WiFi dan password baru.
                    </blockquote>
                ',
                'urutan' => 4,
                'is_published' => true,
                'created_by' => $adminId,
            ],
            [
                'judul' => 'Cara Mengganti Nama WiFi & Password Modem HUAWEI F663NV3a (X PON ONU)',
                'slug' => 'cara-ganti-nama-wifi-password-huawei-f663nv3a',
                'kategori' => 'Modem',
                'ringkasan' => 'Tutorial langkah demi langkah merubah nama WiFi dan password pada modem ONT HUAWEI F663NV3a menggunakan IP default 192.168.1.1.',
                'konten' => '
                    <p>Modem <strong>HUAWEI F663NV3a</strong> menggunakan IP default 192.168.1.1 untuk konfigurasinya. Simak panduan mengganti nama WiFi dan passwordnya di bawah ini:</p>
                    
                    <h3 class="fw-bold mt-4">Langkah 1: Hubungkan ke Jaringan</h3>
                    <p>Pastikan Anda terhubung ke sinyal WiFi modem atau menggunakan kabel LAN.</p>
                    
                    <h3 class="fw-bold mt-4">Langkah 2: Akses Web GUI Modem</h3>
                    <p>Buka browser Anda dan akses IP address: <strong class="text-primary">192.168.1.1</strong>.</p>
                    
                    <h3 class="fw-bold mt-4">Langkah 3: Login Admin</h3>
                    <p>Masukkan username dan password admin bawaan:</p>
                    <ul>
                        <li>Username: <code>admin</code> | Password: <code>admin</code></li>
                        <li>Username: <code>user</code> | Password: <code>user</code></li>
                    </ul>
                    
                    <h3 class="fw-bold mt-4">Langkah 4: Ganti Nama WiFi & Password</h3>
                    <ol>
                        <li>Pilih menu <strong>Network</strong> pada menu utama bagian atas.</li>
                        <li>Pilih sub-menu <strong>WLAN</strong> -> <strong>WLAN Security</strong> atau <strong>Multi-SSID Settings</strong>.</li>
                        <li>Untuk mengubah nama WiFi: ubah teks pada kolom <strong>SSID Name</strong>.</li>
                        <li>Untuk mengubah password: cari opsi <strong>WPA Passphrase</strong> atau <strong>WPA PreSharedKey</strong>, lalu masukkan password baru.</li>
                        <li>Klik <strong>Submit</strong> atau <strong>Apply</strong> untuk menyimpan pengaturan Anda.</li>
                    </ol>
                ',
                'urutan' => 5,
                'is_published' => true,
                'created_by' => $adminId,
            ],
            [
                'judul' => 'Cara Mengganti Nama WiFi & Password Modem HUAWEI HG6145D2',
                'slug' => 'cara-ganti-nama-wifi-password-huawei-hg6145d2',
                'kategori' => 'Modem',
                'ringkasan' => 'Langkah mudah mengubah SSID (nama WiFi) dan kata sandi WiFi untuk perangkat modem router HUAWEI HG6145D2.',
                'konten' => '
                    <p>Berikut adalah cara mengakses dan mengubah setelan WiFi pada modem GPON ONT <strong>HUAWEI HG6145D2</strong>:</p>
                    
                    <h3 class="fw-bold mt-4">Langkah-langkah Ganti WiFi:</h3>
                    <ol>
                        <li>Sambungkan HP/Laptop Anda ke WiFi modem.</li>
                        <li>Buka web browser, buka halaman <strong class="text-primary">192.168.1.1</strong>.</li>
                        <li>Login menggunakan akun admin standar (Username: <code>admin</code> | Password: <code>admin</code>) atau akun superadmin jika disediakan.</li>
                        <li>Akses menu <strong>Network Settings</strong> -> <strong>WLAN Settings</strong>.</li>
                        <li>Pada bagian <strong>SSID Settings</strong>, ubah nama WiFi pada kolom <strong>SSID Name</strong>.</li>
                        <li>Pada bagian <strong>Security Settings</strong>, masukkan sandi baru Anda di kolom <strong>WPA PreSharedKey</strong>.</li>
                        <li>Klik tombol <strong>Apply</strong> untuk menerapkan perubahan.</li>
                    </ol>
                ',
                'urutan' => 6,
                'is_published' => true,
                'created_by' => $adminId,
            ],
            [
                'judul' => 'Cara Mengganti Nama WiFi & Password Modem ZTE F670L Dual Band',
                'slug' => 'cara-ganti-nama-wifi-password-zte-f670l',
                'kategori' => 'Modem',
                'ringkasan' => 'Panduan konfigurasi nama WiFi & password pada modem Dual Band ZTE F670L (2.4GHz & 5GHz) dengan IP 192.168.1.1.',
                'konten' => '
                    <p>Modem <strong>ZTE F670L</strong> mendukung teknologi Dual Band (memancarkan 2 sinyal WiFi sekaligus yaitu 2.4GHz untuk jangkauan luas dan 5GHz untuk kecepatan tinggi). Berikut cara mengubah nama WiFi dan password pada kedua frekuensi tersebut:</p>
                    
                    <h3 class="fw-bold mt-4">Langkah 1: Hubungkan Perangkat</h3>
                    <p>Hubungkan HP atau laptop Anda ke jaringan WiFi ZTE F670L.</p>
                    
                    <h3 class="fw-bold mt-4">Langkah 2: Masuk ke IP Address Modem</h3>
                    <p>Buka browser, lalu ketik URL <strong class="text-primary">192.168.1.1</strong> dan tekan Enter.</p>
                    
                    <h3 class="fw-bold mt-4">Langkah 3: Login User</h3>
                    <p>Gunakan kredensial login berikut:</p>
                    <ul>
                        <li>Username: <code>user</code> | Password: <code>user</code></li>
                        <li>Username: <code>admin</code> | Password: <code>telkomasean</code> atau <code>admin</code></li>
                    </ul>
                    
                    <h3 class="fw-bold mt-4">Langkah 4: Ubah Konfigurasi WiFi</h3>
                    <ol>
                        <li>Pilih menu utama <strong>Local Network</strong> pada tab atas.</li>
                        <li>Klik sub-menu <strong>WLAN</strong> di sisi kiri, lalu pilih <strong>WLAN SSID Configuration</strong>.</li>
                        <li>Di sini Anda akan melihat beberapa SSID. Yang aktif biasanya adalah:
                            <ul>
                                <li><strong>SSID1 (2.4GHz):</strong> Ubah <strong>SSID Name</strong> untuk nama WiFi 2.4GHz, dan <strong>WPA Passphrase</strong> untuk passwordnya.</li>
                                <li><strong>SSID5 (5GHz):</strong> Ubah <strong>SSID Name</strong> untuk nama WiFi 5GHz, dan <strong>WPA Passphrase</strong> untuk passwordnya.</li>
                            </ul>
                        </li>
                        <li>Klik tombol <strong>Apply</strong> di setiap bagian SSID setelah Anda menggantinya.</li>
                    </ol>
                ',
                'urutan' => 7,
                'is_published' => true,
                'created_by' => $adminId,
            ]
        ];

        foreach ($data as $item) {
            Tutorial::updateOrCreate(['slug' => $item['slug']], $item);
        }
    }
}

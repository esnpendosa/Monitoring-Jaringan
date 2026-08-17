# Breakdown Aplikasi Manajemen Jaringan FTTH (Peta + Monitoring + ACS)

## 1. Konsep Dasar (sesuai catatan)

Struktur hierarki jaringan mengikuti alur:

```
OLT / Server  →  ODC  →  ODP/FAT  →  Pelanggan (ONT/ONU)
```

Setiap titik di hierarki ini akan direpresentasikan sebagai marker di peta (Leaflet.js), dan setiap kabel penghubung antar titik direpresentasikan sebagai polyline yang bisa diberi label.

Status tiap titik (khususnya ODP/port pelanggan) direpresentasikan biner:
- `0` = offline / gangguan
- `1` = online / normal

---

## 2. Modul Peta (Leaflet.js — Free)

### Layer & Marker
| Level | Ikon Marker | Data yang disimpan |
|---|---|---|
| OLT / Server | Ikon server | Nama, lokasi, kapasitas PON port |
| ODC | Ikon kotak besar | Nama, lokasi, kapasitas core, ODP turunan |
| ODP / FAT | Ikon kotak kecil | Nama, lokasi, kapasitas port (8/16/24), status agregat |
| Pelanggan (ONT) | Ikon rumah | Nama pelanggan, no. pelanggan, ODP induk, status 0/1 |

### Kabel (Polyline)
- Digambar antar 2 titik (OLT→ODC, ODC→ODP, ODP→Pelanggan)
- Klik kabel → popup untuk **label kabel** (nama/kode, jumlah core, tipe: feeder/distribusi/drop core)
- Warna otomatis mengikuti status:
  - 🟢 Hijau = semua status di bawahnya online (1)
  - 🔴 Merah = ada status offline (0) → indikasi kabel putus/gangguan
  - 🟡 Kuning = redaman tinggi (warning, belum putus total)

### Tools
- `Leaflet.draw` → gambar/edit polyline & marker langsung di peta
- `Leaflet.markercluster` → biar marker ODP/pelanggan yang banyak tidak menumpuk

---

## 3. Modul Status Real-Time (0/1)

Karena software tidak bisa "membaca" fisik kabel secara langsung, status 0/1 didapat dari data perangkat:

**Sumber data:**
- OLT (via SNMP) → status ONU per port (online/offline, RX power/redaman)
- ACS (via TR-069) → status CPE (last inform time, sinyal)

**Logika deteksi gangguan kabel:**
1. Sistem polling status tiap ONU secara berkala (misal tiap 1–5 menit)
2. Kalau 1 pelanggan status `0` → dicatat sebagai gangguan individu (kemungkinan ONT/CPE rusak)
3. Kalau **banyak pelanggan dalam 1 ODP yang sama** serentak jadi `0` → sistem tandai **kabel distribusi ODP tersebut** sebagai putus (bukan sekadar 1 unit rusak)
4. Kalau **seluruh ODP di bawah 1 ODC** serentak `0` → sistem tandai **kabel feeder OLT→ODC** sebagai putus
5. Kabel terkait di peta otomatis berubah warna jadi merah

---

## 3.1 Dua Tipe Kabel: Real-Time vs Label Manual

Tidak semua kabel perlu/bisa dipasang alat monitoring fisik (RFTS/OTDR). Jadi tiap kabel di sistem punya atribut `monitoring_type`:

| Tipe | `monitoring_type` | Sumber status | Perilaku di peta |
|---|---|---|---|
| Kabel dengan RFTS terpasang | `realtime` | API RFTS (OTDR wavelength 1625/1650nm) | Warna otomatis update terus-menerus, ada info titik putus (jarak dari ODC) |
| Kabel tanpa RFTS | `manual` / `label_only` | Tidak ada sensor otomatis — status disimpulkan dari agregasi ONU (lihat poin 3) atau **diupdate manual oleh teknisi** saat cek lapangan | Tetap muncul di peta dengan label (nama, kode, jumlah core, catatan), tapi warna status hanya berubah kalau ada laporan/observasi (manual update) atau lewat inferensi status ONU di bawahnya |

Jadi setiap kabel — real-time atau tidak — tetap punya **label & keterangan** yang bisa diisi (nama kabel, tipe, jumlah core, catatan kondisi), sesuai konsep di catatan kamu. Bedanya cuma sumber update status-nya: otomatis dari alat vs manual/inferensi.

### Modul RFTS (untuk kabel yang support real-time)

- Alat RFTS (brand umum: EXFO, VeEX, Fiberizon) scan kondisi fisik kabel terus-menerus lewat wavelength khusus (1625/1650nm) **tanpa ganggu trafik data pelanggan**
- Laravel backend polling/consume API RFTS (biasanya REST atau SNMP tergantung vendor) secara berkala
- Data yang ditarik: status kabel (baik/putus), titik putus (jarak dari ODC dalam meter), redaman
- Kalau ada perubahan status → update kolom status di tabel `kabel` + trigger alert + kabel di peta otomatis berubah warna, plus muncul marker titik dugaan putus di sepanjang polyline (dihitung dari jarak yang dilaporkan RFTS)

---

## 4. Modul Notifikasi

| Trigger | Level | Channel |
|---|---|---|
| 1 pelanggan offline > 5 menit | Info | Dashboard saja |
| 1 ODP: sebagian besar port offline bersamaan | Warning | Dashboard + WhatsApp/Telegram |
| 1 ODP: seluruh port offline | Critical | Dashboard + WhatsApp/Telegram + Email |
| Redaman ONU melebihi ambang batas (misal > -27dBm) | Warning | Dashboard |

Channel notifikasi bisa pakai:
- WhatsApp Gateway (Fonnte/Wablas) — gratis untuk skala kecil, berbayar untuk volume besar
- Telegram Bot API — gratis
- Email SMTP — gratis

---

## 5. Modul Monitoring ACS (TR-069)

- Gunakan **GenieACS** (open-source, gratis) sebagai server ACS
- Aplikasi kita konsumsi data dari REST API GenieACS:
  - Status CPE (online/offline, last inform)
  - RX/TX optical power
  - Serial number, firmware
- Tiap device di ACS dikaitkan (mapping) ke marker pelanggan di peta lewat serial number / no pelanggan

---

## 6. Struktur Data (Database)

```
olt          (id, nama, lokasi_lat, lokasi_lng, kapasitas_pon)
odc          (id, olt_id, nama, lokasi_lat, lokasi_lng, kapasitas_core)
odp          (id, odc_id, nama, lokasi_lat, lokasi_lng, kapasitas_port)
pelanggan    (id, odp_id, nama, no_pelanggan, serial_ont, lokasi_lat, lokasi_lng, status)
kabel        (id, label, tipe[feeder/distribusi/drop], monitoring_type[realtime/manual],
              from_type, from_id, to_type, to_id, geometry, jumlah_core, status,
              titik_putus_meter[nullable], catatan, updated_by, updated_at)
alert        (id, ref_type, ref_id, level[info/warning/critical], pesan, waktu, status_resolved)
rfts_reading (id, kabel_id, status, redaman, jarak_putus_meter, waktu_baca)
```

`geometry` di tabel kabel disarankan disimpan sebagai array koordinat `[[lat,lng],[lat,lng],...]` agar polyline bisa mengikuti jalur kabel yang tidak lurus (ikut tiang/rute jalan).

---

## 7. Tech Stack yang Disarankan (Laravel)

| Bagian | Rekomendasi | Alasan |
|---|---|---|
| Peta | Leaflet.js + OpenStreetMap | Gratis, ringan |
| Gambar kabel/marker | Leaflet.draw | Gratis, built-in editing |
| Backend | **Laravel** (PHP) | Sesuai request; ekosistem lengkap (auth, queue, scheduler bawaan) |
| Frontend | Blade + Alpine.js, atau Laravel + Vue/React (kalau butuh dashboard lebih reaktif) | Fleksibel sesuai kompleksitas UI |
| Database | **MySQL/MariaDB** atau **PostgreSQL + PostGIS** | PostGIS lebih baik kalau butuh query geografis kompleks (radius, jarak antar titik); MySQL cukup kalau geometry cuma disimpan sebagai JSON koordinat |
| Realtime update | **Laravel Reverb** (WebSocket bawaan Laravel, gratis, self-hosted) atau Pusher | Dashboard/peta update otomatis tanpa refresh saat status kabel berubah |
| Polling OLT via SNMP | Package `php-snmp` (ext) atau `nettools/snmp` | Cek status ONU per port berkala |
| Scheduler polling | **Laravel Scheduler** (`schedule:run` via cron) + **Laravel Queue** (untuk job async, misal kirim notifikasi) | Sudah built-in di Laravel, tidak perlu tool tambahan |
| Integrasi RFTS | HTTP Client Laravel (`Http::get()`) konsumsi REST API vendor RFTS, dijalankan lewat scheduled job | Laravel HTTP client sudah cukup untuk ini |
| ACS | GenieACS (open-source, servernya terpisah) + Laravel konsumsi REST API-nya | GenieACS tidak perlu ditulis ulang, tinggal diintegrasikan |
| Notifikasi | Fonnte/Wablas (WA) atau Telegram Bot API, dikirim lewat Laravel Notification + Queue | Gratis/murah, dan Laravel Notification class memudahkan multi-channel (WA, Telegram, email sekaligus) |

---

## 8. Urutan Pengembangan (Roadmap)

1. **Setup peta dasar (Laravel + Leaflet)** — gambar/label marker & kabel manual dulu, termasuk field `monitoring_type` (realtime/manual) dan catatan/label bebas — ini yang paling cepat kelihatan hasilnya
2. **Database & CRUD** — simpan OLT/ODC/ODP/pelanggan/kabel ke database (Eloquent model + migration), tampil di peta
3. **Integrasi SNMP ke OLT** — polling status ONU via Laravel Scheduler, simpan ke database
4. **Logika deteksi gangguan (kabel manual)** — agregasi status ONU per ODP/ODC, update warna kabel `manual` otomatis dari inferensi ini
5. **Integrasi RFTS (kabel realtime)** — job scheduler konsumsi API RFTS, update status + titik putus untuk kabel `realtime`
6. **Sistem notifikasi** — Laravel Notification (WA/Telegram/email) saat status critical, pakai Queue biar tidak blocking
7. **Integrasi ACS (GenieACS)** — tarik data CPE, link ke marker pelanggan
8. **Dashboard monitoring** — ringkasan uptime, history gangguan, laporan, filter kabel by tipe monitoring

---

## Catatan Penting

- Kabel yang **tidak** punya alat RFTS tetap bisa dipetakan & diberi label lengkap (nama, tipe, jumlah core, catatan kondisi) — statusnya cukup diinferensi dari status ONU/CPE di bawahnya (via SNMP/ACS), atau diupdate manual oleh teknisi saat cek lapangan. Tidak semua kabel wajib punya sensor otomatis untuk bisa dikelola di sistem ini.
- Kabel yang **punya** RFTS mendapat status real-time otomatis + titik putus presisi, tanpa perlu tunggu laporan pelanggan.
- Campuran dua tipe ini normal di lapangan (biasanya RFTS hanya dipasang di kabel feeder/backbone yang kritikal, sementara drop core ke rumah pelanggan cukup diinferensi dari status ONU).

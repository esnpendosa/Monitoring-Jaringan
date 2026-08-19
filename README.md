# 🌐 Rozitech Network Manager
### Sistem Manajemen & Monitoring Jaringan RTRWNet & FTTH Web GIS

> **Pengembang**: Rozitech  
> **Fokus**: Web GIS FTTH, TR-069 GenieACS, Billing Automatic, WhatsApp Gateway & Auto-Sync Jaringan  
> **Repository**: https://github.com/esnpendosa/RTRWNET

---

## 📋 Daftar Isi

1. [Tentang Sistem](#-tentang-sistem)
2. [Fitur Utama](#-fitur-utama)
3. [Teknologi yang Digunakan](#-teknologi-yang-digunakan)
4. [Persyaratan Sistem](#-persyaratan-sistem)
5. [Panduan Instalasi Lokal](#-panduan-instalasi-lokal)
6. [Konfigurasi Environment](#-konfigurasi-environment)
7. [Panduan Deployment Lengkap di aaPanel (VPS Linux)](#-panduan-deployment-lengkap-di-aapanel-vps-linux)
8. [Panduan GenieACS TR-069 Server](#-panduan-genieacs-tr-069-server)
9. [Panduan WhatsApp Bot](#-panduan-whatsapp-bot)
10. [Integrasi REST API (Flutter Mobile)](#-rest-api-integration-flutter-mobile)
11. [Troubleshooting](#-troubleshooting)

---

## 📖 Tentang Sistem

**Rozitech Network Manager** adalah aplikasi profesional berbasis web untuk pengawasan, pengoperasian, dan manajemen jaringan RTRWNet serta infrastruktur FTTH (Fiber to the Home). Aplikasi ini menggabungkan:

- 🗺️ **FTTH Web GIS** — Peta interaktif topologi jaringan (OLT, ODC, ODP, ONT, Kabel Fiber Optik, Tiang Tumpu, Joint Closure) dengan auto-zoom dan filter status real-time.
- 🛰️ **TR-069 GenieACS Auto Configuration Server** — Pengendalian dan monitoring ONT/modem pelanggan secara jarak jauh.
- 🤖 **WhatsApp Gateway & Bot (Baileys)** — Layanan mandiri pelanggan, notifikasi otomatis, dan konfirmasi via pairing code.
- 💳 **Billing & Payment Automation** — Pencetakan nota PDF, ekspor CSV BOM Microsoft Excel, serta isolir otomatis Mikrotik.
- 🔄 **Real-Time Auto Connectivity Ping Check** — Deteksi koneksi otomatis real-time ke semua IP Address perangkat (OLT/ONT) baru maupun lama.

---

## ✨ Fitur Utama

### 1. 🗺️ FTTH Web GIS & Map Widget
- **Google Earth Satellite Tiles**: Peta foto satelit presisi tinggi.
- **Topologi Lengkap**: OLT, ODC, ODP, ONT/Pelanggan, Tiang, Joint Closure, dan Jalur Kabel.
- **Badge Filter Interaktif**: Filter badge status (`Online`, `Offline`, `Isolir`, `Perbaikan`) dengan auto-zoom otomatis ke kluster marker.
- **Auto Connectivity Ping**: Peta dan sidebar otomatis mengevaluasi status koneksi real-time setiap IP perangkat baru.

### 2. 🛰️ TR-069 GenieACS Integration
- Pengambilan data optik (RX Power, Baseline dBm, Serial Number) secara otomatis.
- Evaluasi status offline perangkat secara akurat (perhitungan Carbon `diffInMinutes`).
- Sinkronisasi instan detail perangkat dan reboot modem jarak jauh.

### 3. 📱 WhatsApp Gateway & Pairing Code
- Pendaftaran perangkat via **Pairing Code** (input nomor HP) yang cepat dan mudah tanpa scan QR Code manual.
- Notifikasi pengingat jatuh tempo dan pengiriman nota tagihan otomatis.

### 4. 📊 Compatibility CSV Export
- Format ekspor CSV menggunakan UTF-8 BOM (`\uFEFF`) dan pemisah titik koma `;` (`sep=;\n`) sehingga Microsoft Excel secara otomatis memisahkan data langsung ke dalam kolom terpisah (A, B, C, D, dst).

---

## 🛠️ Teknologi yang Digunakan

| Komponen | Teknologi |
|---|---|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Blade + Vite + Bootstrap 5 |
| Database Utama | MySQL 8.0+ |
| ACS Server | GenieACS TR-069 + MongoDB |
| WhatsApp Bot | Node.js + Baileys Library |
| GIS / Peta | Leaflet.js (Google Earth Satellite Tiles) |
| API Mobile | Laravel Sanctum (Restful API) |

---

## 📦 Persyaratan Sistem

| Software | Versi Minimum | Keterangan |
|---|---|---|
| **PHP** | 8.2+ | Ekstensi: `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `gd`, `curl` |
| **Composer** | 2.2+ | Dependency manager PHP |
| **Node.js** | 18.x / 20.x LTS | Untuk WhatsApp Bot & GenieACS |
| **MySQL** | 8.0+ / MariaDB 10.5+ | Database utama |
| **MongoDB** | 4.4+ | Database internal GenieACS TR-069 |

---

## 🚀 Panduan Instalasi Lokal

```bash
# 1. Clone Repositori
git clone https://github.com/esnpendosa/RTRWNET.git net.rozitech.co.id
cd net.rozitech.co.id

# 2. Install Dependensi PHP
composer install

# 3. Setup Environment
cp .env.example .env
php artisan key:generate

# 4. Migrasi Database & Storage Link
php artisan migrate
php artisan storage:link

# 5. Clear & Cache
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 6. Menjalankan Server Lokal
php artisan serve
```

---

## 🌐 Panduan Deployment Lengkap di aaPanel (VPS Linux)

### Langkah 1: Buat Website & Database di aaPanel
1. Masuk ke Dashboard **aaPanel** -> Buka menu **Website** -> Klik **Add Site**.
2. Masukkan domain (misal: `dev.rozitech.co.id`), pilih **PHP 8.2** atau **PHP 8.3**, centang **Create Database** (nama database: `wifibaru`).
3. Upload seluruh sumber file project Laravel Anda ke direktori: `/www/wwwroot/dev.rozitech.co.id`.
4. Buka **Site Settings**:
   - **Site Directory**: Ubah Running Directory ke **`/public`** -> Klik **Save**.
   - **URL Rewrite**: Pilih preset **`laravel`** -> Klik **Save**.

### Langkah 2: Perintah Setup Root Terminal (VPS)
Masuk ke terminal VPS Anda sebagai **`root`**:

```bash
# 1. Masuk sebagai root user
sudo su

# 2. Update Composer ke versi 2.2+
composer self-update

# 3. Atur Hak Akses & Permission
cd /www/wwwroot/dev.rozitech.co.id
chown -R www:www /www/wwwroot/dev.rozitech.co.id
chmod -R 777 /www/wwwroot/dev.rozitech.co.id/storage
chmod -R 777 /www/wwwroot/dev.rozitech.co.id/bootstrap/cache

# 4. Install Dependensi & Migrate
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link

# 5. Optimasi Cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache

# 6. Fix Git Safe Directory & Ownership Final
git config --global --add safe.directory /www/wwwroot/dev.rozitech.co.id
chown -R www:www /www/wwwroot/dev.rozitech.co.id
```

### Langkah 3: Setup Worker WhatsApp Bot (Supervisor aaPanel)
1. Install plugin **Node.js Version Manager** di aaPanel App Store (pilih Node.js v18 / v20 LTS).
2. Install dependensi bot:
   ```bash
   cd /www/wwwroot/dev.rozitech.co.id/whatsapp-bot
   npm install
   ```
3. Buka **Supervisor Manager** di aaPanel -> Klik **Add Daemon**:
   - **Name**: `whatsapp-bot`
   - **Run User**: `www`
   - **Directory**: `/www/wwwroot/dev.rozitech.co.id/whatsapp-bot`
   - **Command**: `node index.js`

### Langkah 4: Setup Cron Job Scheduler
Buka menu **Cron** di aaPanel -> Tambahkan task baru:
- **Type**: `Shell Script`
- **Period**: `N Minutes` (1 Minute)
- **Script**:
  ```bash
  cd /www/wwwroot/dev.rozitech.co.id && php artisan schedule:run >> /dev/null 2>&1
  ```

---

## 🛰️ Panduan GenieACS TR-069 Server

GenieACS dijalankan di VPS Linux menggunakan **Systemd Service** (Port 7547 CWMP untuk Modem, Port 7557 NBI API untuk Laravel).

```bash
# Install GenieACS secara global (sebagai root)
npm install -g genieacs@1.2.9

# Buat Systemd Services (Copy-Paste sekaligus di terminal root)
cat <<EOF > /etc/systemd/system/genieacs-cwmp.service
[Unit]
Description=GenieACS CWMP
After=network.target

[Service]
User=root
ExecStart=/usr/local/bin/genieacs-cwmp
Restart=always

[Install]
WantedBy=multi-user.target
EOF

cat <<EOF > /etc/systemd/system/genieacs-nbi.service
[Unit]
Description=GenieACS NBI
After=network.target

[Service]
User=root
ExecStart=/usr/local/bin/genieacs-nbi
Restart=always

[Install]
WantedBy=multi-user.target
EOF

cat <<EOF > /etc/systemd/system/genieacs-fs.service
[Unit]
Description=GenieACS FS
After=network.target

[Service]
User=root
ExecStart=/usr/local/bin/genieacs-fs
Restart=always

[Install]
WantedBy=multi-user.target
EOF

cat <<EOF > /etc/systemd/system/genieacs-ui.service
[Unit]
Description=GenieACS UI
After=network.target

[Service]
User=root
ExecStart=/usr/local/bin/genieacs-ui
Restart=always

[Install]
WantedBy=multi-user.target
EOF

# Systemd Reload & Enable
systemctl daemon-reload
systemctl enable --now genieacs-cwmp genieacs-nbi genieacs-fs genieacs-ui
```

*Pastikan Port `7547` (CWMP) dan Port `7557` (NBI API) dibuka pada menu **Security/Firewall** aaPanel.*

---

## 📱 REST API Integration (Flutter Mobile)

Endpoint REST API dilindungi oleh middleware `auth:sanctum`:

- `POST /api/auth/login` — Login pengguna & mengembalikan token akses.
- `GET /api/pelanggan` — Daftar pelanggan & koordinat GPS.
- `GET /api/tagihan` — Data tagihan & status pembayaran.
- `GET /api/laporan/rekap-pembayaran` — Rekapitulasi laporan keuangan real-time.
- `GET /api/peta/pelanggan` — Koordinat marker pelanggan untuk Google Maps / Flutter Maps.

---

## 📄 Lisensi

Dikembangkan oleh **Rozitech** untuk Pengelolaan dan Monitoring Jaringan RTRWNet & FTTH.  
**Status**: ✅ Production Ready & Deployed.

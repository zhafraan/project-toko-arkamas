<p align="center">
  <h1 align="center">🏪 Toko Arkamas</h1>
  <p align="center">
    <strong>Sistem Point of Sale & Manajemen Inventori untuk Distributor Gas LPG</strong>
  </p>
  <p align="center">
    <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 13">
    <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3+">
    <img src="https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
    <img src="https://img.shields.io/badge/Vite-8-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite 8">
    <img src="https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge" alt="License">
  </p>
</p>

---

## 📋 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Fitur Utama](#-fitur-utama)
- [Tech Stack](#-tech-stack)
- [Arsitektur Sistem](#-arsitektur-sistem)
- [Prasyarat](#-prasyarat)
- [Instalasi](#-instalasi)
- [Akun Default](#-akun-default)
- [Struktur Proyek](#-struktur-proyek)

---

## 📖 Tentang Proyek

**Toko Arkamas** adalah aplikasi web Point of Sale (POS) yang dirancang khusus untuk kebutuhan operasional distributor dan toko gas LPG. Aplikasi ini mencakup seluruh siklus bisnis mulai dari penerimaan stok dari agen, pencatatan penjualan, manajemen pelanggan, pelacakan piutang, hingga peminjaman tabung — semuanya dalam satu platform terintegrasi dengan sistem audit trail yang komprehensif.

### Mengapa Toko Arkamas?

| Masalah | Solusi |
|---------|--------|
| Pencatatan manual rawan kesalahan | Sistem POS digital dengan validasi otomatis |
| Piutang sulit dilacak | Pelacakan piutang per pelanggan dengan riwayat pembayaran |
| Stok tabung membingungkan (isi/kosong/dipinjam) | Manajemen stok multi-status dengan penyesuaian terekam |
| Tidak ada jejak audit | Soft delete + deletion log untuk semua data kritis |

---

## ✨ Fitur Utama

### 💳 Point of Sale (Kasir)
- Transaksi penjualan refill, tabung isi, pinjam tabung, dan plus tabung
- Dukungan pelanggan opsional (wajib untuk piutang & pinjam tabung)
- Metode pembayaran tunai / transfer dengan status lunas / piutang
- Cetak struk / receipt per transaksi
- Riwayat transaksi harian dengan filter

### 📦 Manajemen Produk & Stok
- Katalog produk dengan status stok: **Isi (filled)**, **Kosong (empty)**, **Dipinjam (borrowed)**
- Toggle aktif / nonaktif produk
- Penyesuaian stok manual dengan pencatatan alasan (audit log)
- Penerimaan dari agen (tukar tabung kosong → isi) dengan admin fee
- Pengarsipan produk via soft delete

### 👥 Pelanggan & Piutang
- Manajemen data pelanggan berdasarkan tipe: **Rumah Tangga**, **UMKM**, **Pengecer**
- Pelacakan piutang per pelanggan secara real-time
- Pembayaran piutang (lunas / sebagian)
- Pembatalan pembayaran piutang (khusus Owner)
- Pengarsipan pelanggan tanpa menghapus riwayat transaksi

### 🔄 Peminjaman Tabung
- Pencatatan peminjaman tabung ke pelanggan
- Pengembalian tabung dengan update stok otomatis
- Pelacakan status pinjaman aktif

### 📊 Laporan & Export
- Laporan penjualan dengan filter periode
- Export ke **PDF** (via DomPDF)
- Export ke **Excel** (via Maatwebsite Excel)

### 🛡️ Keamanan & Audit
- Autentikasi dengan rate limiting (throttle)
- Role-based access control: **Owner**, **Admin**, **Kasir**
- Soft delete pada data kritis (produk, pelanggan, pengguna, penerimaan agen)
- Riwayat penghapusan & penyesuaian stok tercatat lengkap
- Proteksi akun sendiri agar tidak terhapus

---

## 🛠️ Tech Stack

| Layer | Teknologi | Versi |
|-------|-----------|-------|
| **Backend** | Laravel | 13.x |
| **Runtime** | PHP | ≥ 8.3 |
| **Frontend** | Blade Templates + Tailwind CSS | 4.x |
| **Build Tool** | Vite | 8.x |
| **Database** | SQLite / MySQL | — |
| **PDF Export** | barryvdh/laravel-dompdf | 3.x |
| **Excel Export** | maatwebsite/excel | 4.x |
| **Linting** | Laravel Pint | 1.x |
| **Testing** | PHPUnit | 12.x |

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────┐
│                      Browser                            │
└──────────────────────┬──────────────────────────────────┘
                       │ HTTP
┌──────────────────────▼──────────────────────────────────┐
│                  Laravel Router                         │
│              (routes/web.php)                           │
│  ┌─────────────────────────────────────────────────┐    │
│  │  Middleware: auth, guest, role:owner, throttle  │    │
│  └─────────────────────────────────────────────────┘    │
└──────────────────────┬──────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────┐
│                  Controllers                            │
│  ┌──────────┐ ┌───────────┐ ┌────────────┐             │
│  │   POS    │ │ Inventory │ │  Customer  │   ...       │
│  └─────┬────┘ └─────┬─────┘ └──────┬─────┘             │
└────────┼────────────┼──────────────┼────────────────────┘
         │            │              │
┌────────▼────────────▼──────────────▼────────────────────┐
│                   Eloquent Models                       │
│  Transaction │ Product │ Customer │ TubeLoan │ ...      │
│  (Soft Deletes, Relationships, Scopes)                  │
└──────────────────────┬──────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────┐
│               SQLite / MySQL Database                   │
└─────────────────────────────────────────────────────────┘
```

---

## 📋 Prasyarat

Pastikan sistem Anda telah terinstall:

| Software | Versi Minimum | Cek Versi |
|----------|--------------|-----------|
| PHP | 8.3+ | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 18+ | `node -v` |
| NPM | 9+ | `npm -v` |

---

## 🚀 Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/zhafraan/project-toko-arkamas.git
cd project-toko-arkamas
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env` sesuai kebutuhan, khususnya konfigurasi database:

```env
# Untuk SQLite (default)
DB_CONNECTION=sqlite

# Untuk MySQL
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=toko_arkamas
# DB_USERNAME=root
# DB_PASSWORD=
```

### 4. Setup Database

```bash
# Untuk SQLite, pastikan file database tersedia
# (biasanya sudah otomatis dibuat oleh Laravel)

# Jalankan migrasi dan seeder
php artisan migrate --seed
```

### 5. Build Assets & Jalankan

```bash
# Development (dengan hot reload)
npm run dev

# Jalankan server
php artisan serve
```

Aplikasi akan tersedia di **http://localhost:8000**

### ⚡ Quick Setup (Alternatif)

```bash
composer setup
php artisan serve
```

Script `composer setup` akan otomatis menjalankan seluruh langkah instalasi (install dependencies, generate key, migrasi, dan build assets).

---

## 🔑 Akun Default

Setelah menjalankan seeder, tersedia 3 akun bawaan:

| Role | Email | Password |
|------|-------|----------|
| **Owner** | `owner@arkamas.com` | `owner001` |
| **Admin** | `admin@arkamas.com` | `admin001` |
| **Kasir** | `kasir@arkamas.com` | `kasir001` |

> ⚠️ **Penting:** Segera ubah password default setelah instalasi pada environment produksi.

---

## 📂 Struktur Proyek

```
toko-arkamas/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php        # Autentikasi login/logout
│   │   │   ├── PosController.php         # Transaksi POS & struk
│   │   │   ├── InventoryController.php   # Stok, produk, penerimaan agen
│   │   │   ├── CustomerController.php    # CRUD pelanggan
│   │   │   ├── DebtController.php        # Piutang & pembayaran
│   │   │   ├── LoanController.php        # Peminjaman tabung
│   │   │   ├── ReportController.php      # Laporan & export
│   │   │   ├── UserController.php        # Manajemen pengguna
│   │   │   ├── DashboardController.php   # Halaman dashboard
│   │   │   └── DeletionLogController.php # Audit trail
│   │   └── Middleware/
│   └── Models/                           # 10 Eloquent models
├── database/
│   ├── migrations/                       # 19 migration files
│   └── seeders/                          # Seeder akun default
├── resources/
│   └── views/
│       ├── layouts/                      # Layout utama
│       ├── pos/                          # Halaman kasir
│       ├── inventory/                    # Halaman inventori
│       ├── customers/                    # Halaman pelanggan
│       ├── debts/                        # Halaman piutang
│       ├── loans/                        # Halaman peminjaman
│       ├── reports/                      # Halaman laporan
│       ├── users/                        # Halaman manajemen user
│       └── dashboard/                    # Halaman dashboard
├── routes/
│   └── web.php                           # Definisi seluruh route
├── tests/
│   └── Feature/                          # Feature tests
├── composer.json
├── package.json
├── vite.config.js
└── README.md
```

<p align="center">
  Dibuat dengan ❤️ untuk <strong>Toko Arkamas</strong>
</p>

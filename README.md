# Toko Arkamas

Sistem Point of Sale (POS) dan Manajemen Stok untuk Toko Gas/LPG. Aplikasi ini dibangun dengan Laravel 11.

## Fitur Utama

### Kasir (POS)
- Penjualan refill/tabung isi, pinjam tabung, plus tabung
- Dukungan pelanggan (opsional untuk umum, wajib untuk piutang/pinjam tabung)
- Metode pembayaran tunai/transfer, lunas/piutang
- Pencetakan struk/receipt
- Riwayat transaksi harian

### Manajemen Produk & Stok
- Katalog produk (isi/kosong/dipinjam)
- Toggle aktif/nonaktif produk
- Penyesuaian stok dengan alasan (audit log)
- Penerimaan dari agen (tukar tabung kosong → isi)
- Pengarsipan produk dengan mekanisme soft delete

### Pelanggan & Piutang
- Manajemen data pelanggan (Rumah Tangga/UMKM/Pengecer)
- Pelacakan piutang per pelanggan
- Pembayaran piutang (lunas/sebagian)
- Pembatalan pembayaran piutang (khusus owner)
- Pelanggan diarsipkan dengan soft delete (riwayat transaksi tetap aman)

### Peminjaman Tabung
- Pencatatan peminjaman tabung
- Pengembalian tabung
- Pelacakan status pinjaman

### Manajemen Pengguna
- Role-based access (Owner/Kasir)
- Proteksi akun sendiri
- Pengarsipan pengguna dengan soft delete
- Validasi password kuat

### Laporan & Audit
- Laporan penjualan
- Export PDF/Excel
- Riwayat penghapusan/arsip
- Riwayat penyesuaian stok

## Teknologi

- **Framework**: Laravel 11
- **Frontend**: Blade + Vite + Tailwind CSS (berdasarkan struktur assets)
- **Database**: SQLite/MySQL
- **Export**: DomPDF, Maatwebsite Excel

## Instalasi

### Persyaratan
- PHP >= 8.2
- Composer
- Node.js & NPM
- Database (SQLite/MySQL)

### Langkah Instalasi

```bash
# Clone repository
git clone https://github.com/[username]/toko-arkamas.git
cd toko-arkamas

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
# Untuk SQLite, buat file database/database.sqlite
touch database/database.sqlite

# Migrasi dan seeder
php artisan migrate --seed

# Build assets
npm run build
# atau untuk development
npm run dev

# Jalankan aplikasi
php artisan serve
```

## Akun Default (dari Seeder)

Lihat `database/seeders/UserSeeder.php` untuk kredensial default.

## Mekanisme Soft Delete

Sejak update ini, penghapusan data penting menggunakan **Soft Deletes** untuk menjaga integritas audit:

- **Produk** - Diarsipkan, bisa dipulihkan jika diperlukan
- **Pelanggan** - Diarsipkan, riwayat transaksi dan piutang tetap utuh
- **Pengguna** - Diarsipkan dengan validasi ketat
- **Penerimaan Agen** - Diarsipkan dengan pembalikan stok otomatis

Semua aksi penghapusan/arsip tetap tercatat di **Riwayat Penghapusan** untuk audit trail.

## Role & Permission

| Fitur | Owner | Kasir |
|---|---|---|
| Dashboard | ✓ | ✓ |
| POS/Kasir | ✓ | ✓ |
| Riwayat Transaksi | ✓ | ✓ (hanya hari ini) |
| Pelanggan | ✓ | ✓ |
| Peminjaman Tabung | ✓ | ✓ |
| Piutang | ✓ | ✓ |
| Stok/Inventori | ✓ | ✓ |
| Kelola Produk | ✓ | - |
| Penerimaan Agen | ✓ | ✓ (catat) |
| Hapus Nota/Penerimaan | ✓ | - |
| Laporan | ✓ | - |
| Manajemen Pengguna | ✓ | - |
| Riwayat Audit | ✓ | - |

## Struktur Database Penting

- `products` - Data produk dengan stok (filled/empty/borrowed)
- `customers` - Data pelanggan
- `transactions` - Nota transaksi
- `transaction_details` - Detail item transaksi
- `tube_loans` - Peminjaman tabung
- `debt_payments` - Pembayaran piutang
- `agent_deliveries` - Penerimaan dari agen
- `stock_adjustments` - Riwayat penyesuaian stok
- `deletion_logs` - Audit log penghapusan/arsip
- `users` - Pengguna sistem

## Kontribusi

Project ini dikembangkan untuk Toko Arkamas. Untuk perubahan besar, silakan buat branch terpisah.

## Lisensi

Proprietary - Toko Arkamas

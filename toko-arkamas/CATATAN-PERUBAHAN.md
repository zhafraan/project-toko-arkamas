# Arkamas Gas — Catatan Perubahan (UI + perbaikan fungsi)

## Cara memasang
1. Ekstrak `toko-arkamas-update.zip` **ke dalam folder proyek Anda** (timpa file yang sama). `.env`, `vendor/`, dan database tidak disentuh.
2. Hapus dua file yang sudah tidak terpakai (opsional): `resources/views/welcome.blade.php` (template bawaan Laravel, tidak ada route-nya) dan `test_render.html` (file kosong).
3. Jalankan `php artisan optimize:clear`. Tidak perlu `composer install` / `npm install` / build Vite — CSS & JS ada di `public/css` dan `public/js`.
4. Jalankan `php artisan test` (lihat bagian "Yang belum terverifikasi").

## 1. Bug fungsi yang ditemukan & diperbaiki

**Stok & transaksi (`PosController`)**
- **Stok kosong tidak pernah bertambah saat Refill, dan stok dipinjam tidak pernah bertambah saat Pinjam Tabung.** Penyebabnya `decrement() || increment()` — `decrement()` mengembalikan angka (selalu "benar") sehingga `increment()` tidak pernah jalan. Akibatnya pengembalian tabung membuat stok "dipinjam" menjadi minus.
- Server tidak memeriksa stok: kasir bisa menjual lebih banyak dari stok. Sekarang dicek (digabung per produk) dan baris produk dikunci agar dua kasir tidak menjual stok yang sama.
- Kasbon tanpa uang muka gagal validasi (kolom kosong dianggap salah). "Lunas" dengan uang kurang diam-diam jadi kasbon walau tanpa pelanggan. Sekarang: kolom kosong = uang pas / DP 0; uang kurang ditolak dengan pesan jelas; pelanggan wajib untuk kasbon & pinjam tabung.
- Error validasi pada kasir (AJAX) mengalihkan ke halaman HTML sehingga muncul "gagal" tanpa pesan. Sekarang membalas JSON 422.
- NIK pelanggan baru dari kasir tidak dicek unik → error database. Sekarang divalidasi.
- Pencarian pelanggan di kasir **otomatis memilih pelanggan terakhir** dari hasil pencarian (risiko nota salah pelanggan). Diganti combobox: tidak ada yang terpilih sebelum diklik, dan menampilkan kasbon yang masih berjalan.

**Angka & laporan**
- **"Uang masuk" (tunai/transfer) membengkak** karena memakai uang yang diserahkan pelanggan, bukan dikurangi kembalian. Sekarang `dibayar − kembalian` di dashboard, laporan, PDF, dan Excel.
- Dashboard: `price_refill` tidak ada di model → harga produk selalu Rp 0; piutang & QRIS tanpa format rupiah; kartu "Bulan Ini" milik kasir menampilkan omzet hari ini; "tabung dipinjam" menghitung jumlah baris pinjaman, bukan jumlah tabung; filter dropdown memakai sintaks Alpine v2 (`@click.away`) padahal Alpine v3 sehingga tidak bisa menutup.
- Laporan: field tanggal harian tampil di semua periode (kelas `.hidden` tidak ada), periode Tahunan menampilkan pilihan Bulan, tombol PDF/Excel memakai filter terakhir yang diterapkan (bukan yang sedang dipilih), Excel menyimpan angka sebagai teks (tidak bisa dijumlah), PDF memakai CSS grid yang tidak didukung dompdf. Semua diperbaiki. "Estimasi Laba Bersih" diganti "Estimasi Laba Kotor" (memang harga jual − harga beli).
- **Zona waktu aplikasi UTC** → nota & dashboard menampilkan jam 7 jam lebih awal. Sekarang `Asia/Jakarta`, bahasa `id`.

**Lainnya**
- Pembayaran piutang: `step="1000"` membuat browser menolak nominal yang bukan kelipatan 1000.
- Nama berapostrof (mis. *Warung D'Arto*) memecah tombol edit/kembalikan karena disisipkan ke `onclick`; nama juga disisipkan ke `innerHTML` tanpa escape. Sekarang memakai atribut `data-*` dan escape.
- Produk dihapus = **riwayat penjualannya ikut terhapus**. Sekarang produk yang pernah terjual tidak bisa dihapus (nonaktifkan saja).
- Menghapus akun yang pernah mencatat transaksi menghasilkan error database; sekarang pesan jelas. Pemilik tidak bisa lagi menurunkan peran akunnya sendiri.
- Rute Kiriman Agen bisa di-POST kasir lewat URL walau menunya disembunyikan → sekarang khusus pemilik. Login diberi throttle 10 percobaan/menit.
- Total stok di halaman Produk hanya menjumlah 20 produk di halaman aktif; sekarang semua produk.
- Pesan sukses/error di Produk & Stok tampil dobel; pesan validasi di modal hilang saat reload — modal "Tambah" kini terbuka lagi dengan isian lama.
- Variabel CSS yang tidak terdefinisi (`--holst-*`, `--grad-*`) dan sisa palet biru lama.

## 2. Tombol / elemen ganda yang dihapus
| Sebelumnya | Sekarang |
|---|---|
| Tombol toggle sidebar di topbar **dan** tombol minimize di sidebar | 1 tombol di topbar |
| Kolom pencarian topbar (hanya mengalihkan ke Riwayat), lonceng notifikasi (juga ke Riwayat) | Dihapus — Riwayat punya pencarian sendiri |
| Avatar di topbar **dan** di sidebar | 1, di sidebar |
| Dashboard: tombol "Buka Kasir" + kartu "Kasir" + menu Kasir | 1 tombol di topbar + menu |
| Pelanggan: nama (link) **dan** ikon mata ke halaman detail | Nama saja |
| Kasir: kolom cari pelanggan **dan** dropdown pelanggan | 1 combobox |
| Kasir: preset "+100rb" dan "100rb" ganda | Uang pas / 50 / 100 / 200 rb |
| Produk: ubah stok di form Edit **dan** di Stock Opname | Stok hanya lewat Stock Opname |
| Notifikasi sukses/error tampil di layout **dan** di halaman | Hanya di layout |

## 3. Perubahan tampilan
- Design system baru satu file (`public/css/app.css`): token warna terang/gelap, radius & bayangan konsisten, angka tabular. Warna merek (indigo `#5b5f97` + kuning) dipertahankan.
- Layout baru: judul halaman di topbar, sidebar bisa diciutkan (diingat), drawer di ponsel, tema gelap tanpa berkedip.
- Dashboard: ringkasan satu baris, grafik omzet 7 hari (data sudah dihitung tapi tidak pernah ditampilkan), stok per produk berupa bar Isi/Kosong/Dipinjam.
- Kasir: produk habis ditandai (tidak disembunyikan), lencana jumlah di kartu, jenis transaksi per baris, kembalian/kurang/sisa kasbon real-time, batas stok di keranjang.
- Ikon: Font Awesome (CDN) diganti ikon SVG lokal; Alpine tidak lagi dipakai. Aplikasi hanya bergantung pada Google Fonts (kosmetik).
- Kasir hanya melihat "Stok Gas" tanpa harga beli (margin).

## 4. Perubahan perilaku yang perlu Anda ketahui
- **Zona waktu `Asia/Jakarta`**: data lama yang tersimpan saat zona UTC akan tampil 7 jam lebih awal. Jika itu data uji, abaikan; jika data asli, set `APP_TIMEZONE=UTC` di `.env` untuk sementara.
- Stok Refill sekarang bertambah di "kosong" — angka stok lama yang sudah salah bisa dikoreksi lewat **Stock Opname**.
- Kiriman Agen: jika stok tabung kosong lebih sedikit dari jumlah yang ditukar, kiriman ditolak (agar tidak minus). Koreksi dulu lewat Stock Opname.

## 5. Yang belum terverifikasi (jujur)
PHP tidak tersedia di lingkungan saya dan jaringan mati, jadi **kode PHP belum pernah saya jalankan**. Yang sudah saya lakukan:
- Membuat renderer Blade-mini untuk mengeksekusi semua template dengan data contoh → 14 halaman × 2 peran ter-render, HTML seimbang, semua JS lolos pengecekan sintaks, semua `route()` ada.
- Menguji di Chromium: 27 skenario kasir (stok, kasbon, kembalian, payload checkout, dll.) dan 41 skenario shell (sidebar, tema, modal, filter laporan, bayar piutang, pengembalian, mobile 390px) — semua lulus. Ini menangkap beberapa bug sungguhan (ikon SVG tidak bisa di-`hidden`, topbar meluber di ponsel).
- Pemeriksaan keseimbangan sintaks untuk semua file PHP yang diubah.

Yang **tidak** bisa saya jalankan: controller/query Eloquent, kompilasi Blade asli, PDF/Excel. Karena itu saya menulis `tests/Feature/PosStockTest.php` dan `PagesSmokeTest.php` (matriks stok, validasi pembayaran, hak akses, smoke test semua halaman). **Mohon jalankan `php artisan test` setelah memasang** — jika ada yang gagal, kirim pesan errornya dan saya perbaiki.

## 6. Temuan lain (tidak saya ubah)
- File `.env` ikut ada di zip yang Anda unggah dan berisi password database. Jangan membagikan zip proyek bersama `.env`.
- Kolom deposit tabung (`deposit_amount`) selalu 0 karena tidak ada input deposit di kasir.
- Direktori `resources/css` & `resources/js` + Vite/Tailwind bawaan tidak dipakai lagi oleh tampilan (aman dibiarkan).

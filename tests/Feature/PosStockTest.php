<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TubeLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji aturan bisnis kasir: matriks stok, validasi pembayaran, dan pinjaman tabung.
 * Jalankan: php artisan test --filter=PosStockTest
 */
class PosStockTest extends TestCase
{
    use RefreshDatabase;

    private function kasir(): User
    {
        return User::firstOrCreate(
            ['email' => 'kasir@test.local'],
            ['name' => 'Kasir Uji', 'password' => 'secret123', 'role' => 'kasir']
        );
    }

    private function product(array $override = []): Product
    {
        return Product::create($override + [
            'name' => 'LPG 3 Kg', 'sku' => 'LPG-03',
            'price_buy' => 14500, 'price_sell' => 18000, 'price_tube' => 150000,
            'stock_filled' => 10, 'stock_empty' => 2, 'stock_borrowed' => 0, 'is_active' => true,
        ]);
    }

    private function customer(): Customer
    {
        return Customer::create(['name' => 'Bu Siti', 'customer_type' => 'umkm']);
    }

    private function checkout(User $user, array $payload)
    {
        return $this->actingAs($user)->postJson(route('pos.store'), $payload);
    }

    private function item(Product $p, string $type, int $qty): array
    {
        return ['product_id' => $p->id, 'transaction_type' => $type, 'qty' => $qty];
    }

    public function test_refill_mengurangi_stok_isi_dan_menambah_stok_kosong(): void
    {
        $p = $this->product();

        $this->checkout($this->kasir(), [
            'items' => [$this->item($p, 'refill', 2)],
            'payment_method' => 'cash', 'payment_status' => 'lunas',
        ])->assertOk()->assertJson(['success' => true, 'total_amount' => 36000]);

        $p->refresh();
        $this->assertSame(8, (int) $p->stock_filled);
        $this->assertSame(4, (int) $p->stock_empty);      // sebelumnya tidak pernah bertambah (bug)
        $this->assertSame(0, (int) $p->stock_borrowed);
    }

    public function test_pinjam_tabung_menambah_stok_dipinjam_dan_mencatat_pinjaman(): void
    {
        $p = $this->product();
        $c = $this->customer();

        $this->checkout($this->kasir(), [
            'items' => [$this->item($p, 'pinjam_tabung', 1)],
            'customer_id' => $c->id,
            'payment_method' => 'cash', 'payment_status' => 'lunas',
        ])->assertOk();

        $p->refresh();
        $this->assertSame(9, (int) $p->stock_filled);
        $this->assertSame(1, (int) $p->stock_borrowed);
        $this->assertSame(2, (int) $p->stock_empty);
        $this->assertSame(1, TubeLoan::where('customer_id', $c->id)->count());
    }

    public function test_plus_tabung_menambah_harga_tabung_dan_hanya_mengurangi_stok_isi(): void
    {
        $p = $this->product();

        $this->checkout($this->kasir(), [
            'items' => [$this->item($p, 'plus_tabung', 1)],
            'payment_method' => 'cash', 'payment_status' => 'lunas',
        ])->assertOk()->assertJson(['total_amount' => 168000]);

        $p->refresh();
        $this->assertSame(9, (int) $p->stock_filled);
        $this->assertSame(2, (int) $p->stock_empty);
        $this->assertSame(0, (int) $p->stock_borrowed);
    }

    public function test_stok_tidak_cukup_ditolak_dan_tidak_mengubah_apa_pun(): void
    {
        $p = $this->product(['stock_filled' => 3]);

        $this->checkout($this->kasir(), [
            'items' => [$this->item($p, 'refill', 2), $this->item($p, 'plus_tabung', 2)], // total 4 > 3
            'payment_method' => 'cash', 'payment_status' => 'lunas',
        ])->assertStatus(422)->assertJsonValidationErrors(['items']);

        $this->assertSame(3, (int) $p->fresh()->stock_filled);
        $this->assertSame(0, Transaction::count());
    }

    /** Batas atas: jumlah beli persis sama dengan stok masih boleh; satu unit lagi tidak. */
    public function test_stok_pas_dengan_jumlah_beli_dapat_diselesaikan(): void
    {
        $p = $this->product(['stock_filled' => 3]);

        $this->checkout($this->kasir(), [
            'items' => [$this->item($p, 'refill', 3)],
            'payment_method' => 'cash', 'payment_status' => 'lunas',
        ])->assertOk();

        $this->assertSame(0, (int) $p->fresh()->stock_filled);
        $this->assertSame(1, Transaction::count());
    }

    /** Dua baris keranjang produk & jenis sama digabung — totalnya yang dihitung server, bukan per baris. */
    public function test_gabungan_dua_baris_satu_produk_dihitung_bersama(): void
    {
        $p = $this->product(['stock_filled' => 3]);

        $this->checkout($this->kasir(), [
            'items' => [
                ['product_id' => $p->id, 'transaction_type' => 'refill', 'qty' => 2],
                ['product_id' => $p->id, 'transaction_type' => 'refill', 'qty' => 2],
            ],
            'payment_method' => 'cash', 'payment_status' => 'lunas',
        ])->assertStatus(422)->assertJsonValidationErrors(['items']);

        $this->assertSame(3, (int) $p->fresh()->stock_filled);
        $this->assertSame(0, Transaction::count());
    }

    public function test_kasbon_dan_pinjam_tabung_wajib_pelanggan(): void
    {
        $p = $this->product();

        $this->checkout($this->kasir(), [
            'items' => [$this->item($p, 'refill', 1)],
            'payment_method' => 'cash', 'payment_status' => 'belum_lunas',
        ])->assertStatus(422)->assertJsonValidationErrors(['customer_id']);

        $this->checkout($this->kasir(), [
            'items' => [$this->item($p, 'pinjam_tabung', 1)],
            'payment_method' => 'cash', 'payment_status' => 'lunas',
        ])->assertStatus(422)->assertJsonValidationErrors(['customer_id']);

        $this->assertSame(0, Transaction::count());
    }

    public function test_lunas_dengan_uang_kurang_ditolak(): void
    {
        $p = $this->product();

        $this->checkout($this->kasir(), [
            'items' => [$this->item($p, 'refill', 2)],
            'payment_method' => 'cash', 'payment_status' => 'lunas', 'paid_amount' => 10000,
        ])->assertStatus(422)->assertJsonValidationErrors(['paid_amount']);

        $this->assertSame(10, (int) $p->fresh()->stock_filled);
    }

    public function test_lunas_tanpa_nominal_dianggap_uang_pas(): void
    {
        $p = $this->product();

        $this->checkout($this->kasir(), [
            'items' => [$this->item($p, 'refill', 1)],
            'payment_method' => 'transfer', 'payment_status' => 'lunas', 'paid_amount' => null,
        ])->assertOk()->assertJson(['paid_amount' => 18000, 'change_amount' => 0, 'remaining_debt' => 0]);
    }

    public function test_kembalian_dihitung_dan_uang_masuk_dashboard_tidak_termasuk_kembalian(): void
    {
        $p = $this->product();
        $user = $this->kasir();

        $this->checkout($user, [
            'items' => [$this->item($p, 'refill', 2)], // 36.000
            'payment_method' => 'cash', 'payment_status' => 'lunas', 'paid_amount' => 50000,
        ])->assertOk()->assertJson(['change_amount' => 14000]);

        $this->actingAs($user)->get('/')->assertOk()
            ->assertViewHas('todayCash', fn ($v) => (float) $v === 36000.0);
    }

    public function test_kasbon_tanpa_dp_tercatat_sebagai_belum_lunas(): void
    {
        $p = $this->product();
        $c = $this->customer();

        $this->checkout($this->kasir(), [
            'items' => [$this->item($p, 'refill', 2)],
            'customer_id' => $c->id,
            'payment_method' => 'cash', 'payment_status' => 'belum_lunas', 'paid_amount' => null,
        ])->assertOk()->assertJson(['remaining_debt' => 36000, 'paid_amount' => 0]);

        $this->assertSame('belum_lunas', Transaction::first()->payment_status);
    }

    public function test_pengembalian_tabung_memindahkan_stok_dari_dipinjam_ke_kosong(): void
    {
        $p = $this->product();
        $c = $this->customer();
        $user = $this->kasir();

        $this->checkout($user, [
            'items' => [$this->item($p, 'pinjam_tabung', 2)],
            'customer_id' => $c->id, 'payment_method' => 'cash', 'payment_status' => 'lunas',
        ])->assertOk();

        $loan = TubeLoan::first();
        $this->actingAs($user)->postJson(route('loans.return', $loan), ['qty_return' => 1])->assertOk();

        $p->refresh();
        $this->assertSame(1, (int) $p->stock_borrowed);
        $this->assertSame(3, (int) $p->stock_empty);
        $this->assertSame('sebagian_kembali', $loan->fresh()->status);
    }

    public function test_pembayaran_piutang_bukan_kelipatan_seribu_diterima(): void
    {
        $p = $this->product();
        $c = $this->customer();
        $user = $this->kasir();

        $this->checkout($user, [
            'items' => [$this->item($p, 'refill', 2)], 'customer_id' => $c->id,
            'payment_method' => 'cash', 'payment_status' => 'belum_lunas',
        ])->assertOk();

        $trx = Transaction::first();
        $this->actingAs($user)->postJson(route('debts.pay', $trx), ['amount_paid' => 25500, 'payment_method' => 'cash'])->assertOk();

        $this->assertEquals(10500, $trx->fresh()->remaining_debt);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji laporan kas cash-basis: uang dihitung pada tanggal diterimanya
 * pembayaran (pembayaran awal nota ATAU cicilan piutang), bukan tanggal nota.
 * Termasuk "uang masuk hari ini" di dashboard.
 * Jalankan: php artisan test --filter=CashBasisReportTest
 */
class CashBasisReportTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::firstOrCreate(
            ['email' => 'owner@test.local'],
            ['name' => 'Owner Uji', 'password' => 'secret123', 'role' => 'owner']
        );
    }

    private function kasir(): User
    {
        return User::firstOrCreate(
            ['email' => 'kasir@test.local'],
            ['name' => 'Kasir Uji', 'password' => 'secret123', 'role' => 'kasir']
        );
    }

    private function product(int $filled = 20): Product
    {
        return Product::create([
            'name' => 'LPG 3 Kg', 'sku' => 'LPG-03',
            'price_buy' => 14500, 'price_sell' => 18000, 'price_tube' => 150000,
            'stock_filled' => $filled, 'stock_empty' => 0, 'stock_borrowed' => 0, 'is_active' => true,
        ]);
    }

    private function customer(): Customer
    {
        return Customer::create(['name' => 'Bu Siti', 'customer_type' => 'umkm']);
    }

    /**
     * Nota kemarin dibayar hari ini: kas kemarin = pembayaran awalnya saja (50.000),
     * kas hari ini = cicilannya (130.000). Sebelum cash-basis, kemarin dihitung
     * 180.000 (nilai paid akhir) dan hari ini 0 — padahal uangnya bergerak hari ini.
     */
    public function test_kas_periode_mengikuti_tanggal_pembayaran_bukan_tanggal_nota(): void
    {
        $p    = $this->product();
        $user = $this->kasir();

        // Nota 180.000 (10 refill × 18.000), bayar awal 50.000 tunai, sisanya piutang
        $this->actingAs($user)->postJson(route('pos.store'), [
            'items'          => [['product_id' => $p->id, 'transaction_type' => 'refill', 'qty' => 10]],
            'customer_id'    => $this->customer()->id,
            'payment_method' => 'cash', 'payment_status' => 'belum_lunas', 'paid_amount' => 50000,
        ])->assertOk()->assertJson(['remaining_debt' => 130000]);

        // Cicilan 130.000 hari ini (transfer) -> lunas
        $nota = Transaction::sole();
        $this->actingAs($user)->post(route('debts.pay', $nota), [
            'amount_paid' => 130000, 'payment_method' => 'transfer',
        ])->assertSessionHasNoErrors();

        // Nota dicatat kemarin (dibayar penuh hari ini)
        $yesterday = now()->subDay()->toDateString();
        Transaction::query()->update(['transaction_date' => now()->subDay()]);

        $this->actingAs($this->owner())->get(route('reports.index', ['type' => 'daily', 'date' => $yesterday]))
            ->assertOk()
            ->assertViewHas('metrics', fn ($m) => (float) $m['totalCash'] === 50000.0
                && (float) $m['totalTransfer'] === 0.0
                && (float) $m['totalRevenue'] === 180000.0);

        $this->actingAs($this->owner())->get(route('reports.index', ['type' => 'daily', 'date' => today()->toDateString()]))
            ->assertOk()
            ->assertViewHas('metrics', fn ($m) => (float) $m['totalCash'] === 0.0
                && (float) $m['totalTransfer'] === 130000.0);
    }

    /** Dashboard: kasbon hari ini tanpa DP lalu dicicil hari ini juga harus terhitung masuk hari ini. */
    public function test_dashboard_uang_masuk_hari_ini_termasuk_cicilan_hari_ini(): void
    {
        $p = $this->product();

        $this->actingAs($this->kasir())->postJson(route('pos.store'), [
            'items'          => [['product_id' => $p->id, 'transaction_type' => 'refill', 'qty' => 10]],
            'customer_id'    => $this->customer()->id,
            'payment_method' => 'cash', 'payment_status' => 'belum_lunas', 'paid_amount' => 0,
        ])->assertOk()->assertJson(['paid_amount' => 0, 'remaining_debt' => 180000]);

        $nota = Transaction::sole();
        $this->actingAs($this->kasir())->post(route('debts.pay', $nota), [
            'amount_paid' => 60000, 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->kasir())->get('/')->assertOk()
            ->assertViewHas('todayCash', fn ($v) => (float) $v === 60000.0);
    }

    /** Kas harian tetap memotong kembalian dan tidak menghitung cicilan di luar periode. */
    public function test_kas_harian_tetap_mengurangi_kembalian(): void
    {
        $p = $this->product();

        $this->actingAs($this->kasir())->postJson(route('pos.store'), [
            'items'          => [['product_id' => $p->id, 'transaction_type' => 'refill', 'qty' => 2]],
            'payment_method' => 'cash', 'payment_status' => 'lunas', 'paid_amount' => 50000,
        ])->assertOk()->assertJson(['change_amount' => 14000]);

        $this->actingAs($this->owner())->get(route('reports.index', [
            'type' => 'daily', 'date' => today()->toDateString(),
        ]))->assertOk()->assertViewHas('metrics', fn ($m) => (float) $m['totalCash'] === 36000.0);
    }
}

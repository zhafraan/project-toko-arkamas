<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DebtPayment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji pelunasan piutang: nota otomatis berstatus "lunas" saat sisa mencapai 0,
 * dan pemilik dapat membatalkan (void) pembayaran yang salah sehingga saldo kembali.
 * Jalankan: php artisan test --filter=DebtSettlementTest
 */
class DebtSettlementTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::firstOrCreate(
            ['email' => 'owner@test.local'],
            ['name' => 'Pemilik', 'password' => 'secret123', 'role' => 'owner']
        );
    }

    private function kasir(): User
    {
        return User::firstOrCreate(
            ['email' => 'kasir@test.local'],
            ['name' => 'Kasir', 'password' => 'secret123', 'role' => 'kasir']
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

    /** Membuat nota piutang 180.000 dengan bayar awal 50.000 (sisa 130.000). */
    private function notaPiutang(User $user, int $paid = 50000): Transaction
    {
        $p = $this->product();
        $this->actingAs($user)->postJson(route('pos.store'), [
            'items'          => [['product_id' => $p->id, 'transaction_type' => 'refill', 'qty' => 10]],
            'customer_id'    => $this->customer()->id,
            'payment_method' => 'cash', 'payment_status' => 'belum_lunas', 'paid_amount' => $paid,
        ])->assertOk();

        return Transaction::sole();
    }

    public function test_pembayaran_penuh_menandai_nota_lunas(): void
    {
        $nota = $this->notaPiutang($this->kasir());

        $res = $this->actingAs($this->kasir())->postJson(route('debts.pay', $nota), [
            'amount_paid' => 130000, 'payment_method' => 'cash',
        ]);

        $res->assertOk()->assertJson(['lunas' => true]);
        $this->assertStringContainsString('LUNAS', $res->json('message'));

        $nota->refresh();
        $this->assertSame('lunas', $nota->payment_status);
        $this->assertSame(0.0, (float) $nota->remaining_debt);
        $this->assertNotNull($nota->paid_at);
    }

    public function test_pembayaran_sebagian_tidak_menandai_lunas(): void
    {
        $nota = $this->notaPiutang($this->kasir());

        $this->actingAs($this->kasir())->postJson(route('debts.pay', $nota), [
            'amount_paid' => 30000, 'payment_method' => 'cash',
        ])->assertOk()->assertJson(['lunas' => false]);

        $nota->refresh();
        $this->assertSame('belum_lunas', $nota->payment_status);
        $this->assertSame(100000.0, (float) $nota->remaining_debt);
        $this->assertNull($nota->paid_at);
    }

    public function test_owner_dapat_membatalkan_pembayaran_dan_saldo_kembali(): void
    {
        $nota = $this->notaPiutang($this->kasir());

        $this->actingAs($this->kasir())->post(route('debts.pay', $nota), [
            'amount_paid' => 130000, 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $nota->refresh();
        $this->assertSame('lunas', $nota->payment_status);

        $payment = DebtPayment::sole();

        $this->actingAs($this->owner())
            ->delete(route('debts.pay.destroy', $payment))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, DebtPayment::count());

        $nota->refresh();
        $this->assertSame('belum_lunas', $nota->payment_status);
        $this->assertSame(130000.0, (float) $nota->remaining_debt);
        $this->assertSame(50000.0, (float) $nota->paid_amount);
        $this->assertNull($nota->paid_at);
    }

    public function test_kasir_tidak_boleh_membatalkan_pembayaran(): void
    {
        $nota = $this->notaPiutang($this->kasir());
        $this->actingAs($this->kasir())->post(route('debts.pay', $nota), [
            'amount_paid' => 130000, 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $payment = DebtPayment::sole();

        $this->actingAs($this->kasir())
            ->delete(route('debts.pay.destroy', $payment))
            ->assertForbidden();

        $this->assertSame(1, DebtPayment::count());
    }

    public function test_membatalkan_satu_dari_dua_cicilan_mengembalikan_saldo_sebagian(): void
    {
        $nota = $this->notaPiutang($this->kasir());

        // Cicilan 1: 60000, Cicilan 2: 70000 -> lunas
        $this->actingAs($this->kasir())->post(route('debts.pay', $nota), [
            'amount_paid' => 60000, 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->kasir())->post(route('debts.pay', $nota), [
            'amount_paid' => 70000, 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $nota->refresh();
        $this->assertSame('lunas', $nota->payment_status);

        $this->actingAs($this->owner())
            ->delete(route('debts.pay.destroy', DebtPayment::orderByDesc('id')->first()))
            ->assertSessionHasNoErrors();

        $nota->refresh();
        $this->assertSame('belum_lunas', $nota->payment_status);
        $this->assertSame(70000.0, (float) $nota->remaining_debt);
        $this->assertSame(110000.0, (float) $nota->paid_amount);
    }
}

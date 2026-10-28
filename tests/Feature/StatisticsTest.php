<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Support\SalesStats;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create(['name' => 'Pemilik', 'email' => 'o@test.local', 'password' => 'secret123', 'role' => 'owner']);
    }

    public function test_halaman_statistik_tampil_kosong_dan_berisi(): void
    {
        $owner = $this->owner();
        foreach (['7d', '30d', 'this_month', 'last_month', 'this_year'] as $r) {
            $this->actingAs($owner)->get(route('statistics.index', ['range' => $r]))->assertOk();
        }

        $p = Product::create(['name' => 'LPG 3 Kg', 'sku' => 'LPG-03', 'price_buy' => 14500, 'price_sell' => 18000,
            'price_tube' => 150000, 'stock_filled' => 10, 'stock_empty' => 2, 'stock_borrowed' => 0, 'is_active' => true]);
        $c = Customer::create(['name' => 'Budi', 'customer_type' => 'umkm']);
        $this->actingAs($owner)->postJson(route('pos.store'), [
            'items' => [['product_id' => $p->id, 'transaction_type' => 'refill', 'qty' => 2]],
            'customer_id' => $c->id, 'payment_method' => 'cash', 'payment_status' => 'lunas',
        ])->assertOk();

        $this->actingAs($owner)->get(route('statistics.index'))
            ->assertOk()->assertSee('LPG 3 Kg')->assertSee('Budi')->assertSee('36.000');
    }

    public function test_statistik_khusus_pemilik_dan_validasi_tanggal(): void
    {
        $kasir = User::create(['name' => 'Kasir', 'email' => 'k@test.local', 'password' => 'secret123', 'role' => 'kasir']);
        $this->actingAs($kasir)->get(route('statistics.index'))->assertForbidden();

        $this->actingAs($this->owner())
            ->get(route('statistics.index', ['range' => 'custom', 'from' => '2026-10-10', 'to' => '2026-10-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_perhitungan_total_produk_dan_pola_waktu(): void
    {
        $trx = [
            ['id' => 1, 'customer_id' => 5, 'customer_name' => 'Sri', 'date' => '2026-10-26 09:15:00', 'total' => 54000, 'method' => 'cash', 'status' => 'lunas'],
            ['id' => 2, 'customer_id' => null, 'customer_name' => null, 'date' => '2026-10-27 17:40:00', 'total' => 18000, 'method' => 'transfer', 'status' => 'belum_lunas'],
        ];
        $lines = [
            ['transaction_id' => 1, 'product_id' => 1, 'product_name' => 'LPG 3 Kg', 'type' => 'refill', 'qty' => 3, 'cost' => 14500, 'subtotal' => 54000],
            ['transaction_id' => 2, 'product_id' => 1, 'product_name' => 'LPG 3 Kg', 'type' => 'refill', 'qty' => 1, 'cost' => 14500, 'subtotal' => 18000],
        ];
        $r = SalesStats::compute($trx, $lines, Carbon::parse('2026-10-22'), Carbon::parse('2026-10-28'));

        $this->assertSame(72000.0, $r['totals']['revenue']);
        $this->assertSame(4, $r['totals']['units']);
        $this->assertEqualsWithDelta(72000 - 4 * 14500, $r['totals']['profit'], 0.01);
        $this->assertCount(7, $r['series']);
        $this->assertSame(1, $r['weekday'][0]['count']); // Senin 26 Okt
        $this->assertSame(1, $r['hours'][17]);
        $this->assertSame('2026-10-26', $r['bestDay']['date']);
    }
}

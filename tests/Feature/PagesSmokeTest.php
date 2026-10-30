<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Memastikan setiap halaman ter-render tanpa error (menguji kompilasi Blade sebenarnya)
 * dan hak akses per peran. Jalankan: php artisan test --filter=PagesSmokeTest
 */
class PagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function seedData(User $user): void
    {
        $p = Product::create([
            'name' => 'LPG 3 Kg', 'sku' => 'LPG-03', 'price_buy' => 14500, 'price_sell' => 18000, 'price_tube' => 150000,
            'stock_filled' => 10, 'stock_empty' => 2, 'stock_borrowed' => 1, 'is_active' => true,
        ]);
        $t = Product::create([
            'name' => 'Telur Ayam', 'sku' => 'TEL-01', 'price_buy' => 25000, 'price_sell' => 30000, 'price_tube' => 0,
            'stock_filled' => 5, 'stock_empty' => 0, 'stock_borrowed' => 0, 'is_active' => true,
        ]);
        $c = Customer::create(['name' => "Warung D'Arto", 'customer_type' => 'umkm']);
        $this->actingAs($user)->postJson(route('pos.store'), [
            'items' => [['product_id' => $p->id, 'transaction_type' => 'pinjam_tabung', 'qty' => 1]],
            'customer_id' => $c->id, 'payment_method' => 'cash', 'payment_status' => 'belum_lunas', 'paid_amount' => 5000,
        ])->assertOk();
        $this->actingAs($user)->postJson(route('pos.store'), [
            'items' => [['product_id' => $t->id, 'transaction_type' => 'jual', 'qty' => 2]],
            'customer_id' => $c->id, 'payment_method' => 'cash', 'payment_status' => 'lunas', 'paid_amount' => 60000,
        ])->assertOk();
    }

    public function test_semua_halaman_pemilik_tampil(): void
    {
        $owner = User::create(['name' => 'Pemilik', 'email' => 'owner@test.local', 'password' => 'secret123', 'role' => 'owner']);
        $this->seedData($owner);
        $trx = Transaction::first();
        $customer = Customer::first();

        foreach ([
            route('dashboard'), route('pos.index'), route('pos.history'), route('pos.receipt', $trx),
            route('customers.index'), route('customers.show', $customer), route('customers.search', ['q' => 'war']),
            route('loans.index'), route('debts.index'), route('inventory.index'), route('inventory.deliveries'),
            route('reports.index'), route('reports.index', ['type' => 'weekly']), route('reports.index', ['type' => 'yearly']),
            route('users.index'), route('statistics.index'),
        ] as $url) {
            $this->actingAs($owner)->get($url)->assertOk();
        }
    }

    public function test_kasir_tidak_bisa_membuka_halaman_pemilik_tetapi_bisa_melihat_stok(): void
    {
        $kasir = User::create(['name' => 'Kasir', 'email' => 'kasir@test.local', 'password' => 'secret123', 'role' => 'kasir']);

        // Halaman khusus pemilik
        foreach ([route('reports.index'), route('users.index')] as $url) {
            $this->actingAs($kasir)->get($url)->assertForbidden();
        }

        // Kasir boleh mencatat penerimaan dari agen
        $p = Product::create([
            'name' => 'LPG 3 Kg', 'sku' => 'LPG-03', 'price_buy' => 14500, 'price_sell' => 18000, 'price_tube' => 150000,
            'stock_filled' => 0, 'stock_empty' => 50, 'stock_borrowed' => 0, 'is_active' => true,
        ]);
        $this->actingAs($kasir)->get(route('inventory.deliveries'))->assertOk();
        $this->actingAs($kasir)->post(route('inventory.storeDelivery'), [
            'product_id' => $p->id, 'delivery_date' => today()->toDateString(),
            'qty_received' => 5, 'admin_fee' => 0,
        ])->assertSessionHasNoErrors();

        // ...tetapi tidak boleh menghapusnya (khusus pemilik)
        $delivery = \App\Models\AgentDelivery::sole();
        $this->actingAs($kasir)->delete(route('inventory.destroyDelivery', $delivery))->assertForbidden();

        foreach ([route('dashboard'), route('pos.index'), route('inventory.index'), route('debts.index')] as $url) {
            $this->actingAs($kasir)->get($url)->assertOk();
        }
    }

    public function test_pemilik_tidak_bisa_menurunkan_peran_sendiri(): void
    {
        $owner = User::create(['name' => 'Pemilik', 'email' => 'owner@test.local', 'password' => 'secret123', 'role' => 'owner']);

        $this->actingAs($owner)->put(route('users.update', $owner), [
            'name' => 'Pemilik', 'email' => 'owner@test.local', 'role' => 'kasir',
        ])->assertSessionHas('error');

        $this->assertSame('owner', $owner->fresh()->role);
    }

    public function test_produk_yang_sudah_terjual_tidak_bisa_dihapus(): void
    {
        $owner = User::create(['name' => 'Pemilik', 'email' => 'owner@test.local', 'password' => 'secret123', 'role' => 'owner']);
        $this->seedData($owner);
        $product = Product::first();

        $this->actingAs($owner)->delete(route('inventory.destroy', $product))->assertSessionHas('error');
        $this->assertNotNull(Product::find($product->id));
    }
}

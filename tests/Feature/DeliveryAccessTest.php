<?php

namespace Tests\Feature;

use App\Models\AgentDelivery;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Akses penerimaan dari agen: kasir boleh mencatat (tanpa menentukan harga),
 * penghapusan tetap khusus pemilik.
 * Jalankan: php artisan test --filter=DeliveryAccessTest
 */
class DeliveryAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role, string $name = 'Uji'): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => 'secret123', 'role' => $role]
        );
    }

    private function product(int $empty = 100): Product
    {
        return Product::create([
            'name' => 'LPG 3 Kg', 'sku' => 'LPG-03',
            'price_buy' => 14500, 'price_sell' => 18000, 'price_tube' => 150000,
            'stock_filled' => 0, 'stock_empty' => $empty, 'stock_borrowed' => 0, 'is_active' => true,
        ]);
    }

    private function owner(): User
    {
        return $this->user('owner@test.local', 'owner', 'Owner Uji');
    }

    private function kasir(): User
    {
        return $this->user('kasir@test.local', 'kasir', 'Kasir Uji');
    }

    private function payload(Product $p, int $qty = 10, ?float $price = null): array
    {
        $data = [
            'product_id'    => $p->id,
            'delivery_date' => today()->toDateString(),
            'qty_received'  => $qty,
            'admin_fee'     => 0,
        ];
        if ($price !== null) {
            $data['buy_price_per_unit'] = $price;
        }
        return $data;
    }

    public function test_kasir_dapat_membuka_halaman_penerimaan(): void
    {
        $this->actingAs($this->kasir())->get(route('inventory.deliveries'))
            ->assertOk()
            ->assertSee('Catat penerimaan');
    }

    public function test_kasir_dapat_mencatat_penerimaan_dengan_harga_default(): void
    {
        $p = $this->product();

        $this->actingAs($this->kasir())->post(route('inventory.storeDelivery'), $this->payload($p, 10))
            ->assertSessionHasNoErrors();

        $d = AgentDelivery::sole();
        $this->assertSame(14500.0, (float) $d->buy_price_per_unit);
        $this->assertSame(10, (int) $p->fresh()->stock_filled);
        $this->assertSame(90, (int) $p->fresh()->stock_empty);
    }

    /** Kasir tidak boleh menaikkan/menurunkan harga — input harga diabaikan. */
    public function test_input_harga_dari_kasir_diabaikan(): void
    {
        $p = $this->product();

        $this->actingAs($this->kasir())
            ->post(route('inventory.storeDelivery'), $this->payload($p, 10, 99999))
            ->assertSessionHasNoErrors();

        $this->assertSame(14500.0, (float) AgentDelivery::sole()->buy_price_per_unit);
    }

    /** Kasir memakai harga agen terakhir produk, bukan default buta. */
    public function test_kasir_memakai_harga_terakhir_produk(): void
    {
        $p     = $this->product();
        $owner = $this->owner();

        // Pemilik mencatat dengan harga naik 15.000
        $this->actingAs($owner)
            ->post(route('inventory.storeDelivery'), $this->payload($p, 20, 15000))
            ->assertSessionHasNoErrors();

        // Kasir mencatat berikutnya tanpa mengisi harga -> pakai 15.000 (bukan 14.500)
        $this->actingAs($this->kasir())
            ->post(route('inventory.storeDelivery'), $this->payload($p, 5))
            ->assertSessionHasNoErrors();

        $this->assertSame(15000.0, (float) AgentDelivery::latest('id')->first()->buy_price_per_unit);
    }

    public function test_kasir_tidak_dapat_menghapus_penerimaan(): void
    {
        $p     = $this->product();
        $owner = $this->owner();

        $this->actingAs($owner)
            ->post(route('inventory.storeDelivery'), $this->payload($p, 10))
            ->assertSessionHasNoErrors();
        $delivery = AgentDelivery::sole();

        $this->actingAs($this->kasir())
            ->delete(route('inventory.destroyDelivery', $delivery))
            ->assertForbidden();

        $this->assertDatabaseHas('agent_deliveries', ['id' => $delivery->id]);
    }
}

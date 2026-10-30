<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeletionLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji perbaikan audit: penukaran stok penerimaan agen, invarian total tabung
 * saat penghapusan, guard hapus pengguna, password policy, validasi laporan,
 * dan header keamanan.
 * Jalankan: php artisan test --filter=HardeningTest
 */
class HardeningTest extends TestCase
{
    use RefreshDatabase;

    private function owner(string $email = 'owner@test.local', string $name = 'Owner Uji'): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => 'secret123', 'role' => 'owner']
        );
    }

    private function product(array $override = []): Product
    {
        return Product::create($override + [
            'name' => 'LPG 3 Kg', 'sku' => 'LPG-03',
            'price_buy' => 14500, 'price_sell' => 18000, 'price_tube' => 150000,
            'stock_filled' => 0, 'stock_empty' => 200, 'stock_borrowed' => 0, 'is_active' => true,
        ]);
    }

    private function deliveryPayload(Product $p, int $qty): array
    {
        return [
            'product_id'         => $p->id,
            'delivery_date'      => today()->toDateString(),
            'qty_received'       => $qty,
            'buy_price_per_unit' => 14500,
            'admin_fee'          => 0,
        ];
    }

    public function test_penerimaan_menukar_stok_isi_dan_kosong(): void
    {
        $p     = $this->product();
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('inventory.storeDelivery'), $this->deliveryPayload($p, 100))
            ->assertSessionHasNoErrors();

        $p->refresh();
        $this->assertSame(100, (int) $p->stock_filled);
        $this->assertSame(100, (int) $p->stock_empty);
        $this->assertSame(200, $p->total_stock);
    }

    public function test_penerimaan_ditolak_jika_stok_kosong_kurang(): void
    {
        $p = $this->product(['stock_empty' => 50]);

        $this->actingAs($this->owner())->post(route('inventory.storeDelivery'), $this->deliveryPayload($p, 100))
            ->assertSessionHasErrors('qty_received');

        $p->refresh();
        $this->assertSame(0, (int) $p->stock_filled);
        $this->assertSame(50, (int) $p->stock_empty);
    }

    /** Harga dari agen opsional: dikosongkan = tarif default 14.500. */
    public function test_harga_agen_default_digunakan_bila_dikosongkan(): void
    {
        $p = $this->product(['stock_empty' => 50]);

        $payload = $this->deliveryPayload($p, 10);
        unset($payload['buy_price_per_unit']);

        $this->actingAs($this->owner())->post(route('inventory.storeDelivery'), $payload)
            ->assertSessionHasNoErrors();

        $delivery = \App\Models\AgentDelivery::sole();
        $this->assertSame(14500.0, (float) $delivery->buy_price_per_unit);
        $this->assertSame(145000.0, (float) $delivery->total_cost);
    }

    /** Regresi bug hapus penerimaan: total tabung harus tetap 200 (tidak membengkak). */
    public function test_hapus_penerimaan_mempertahankan_total_tabung(): void
    {
        $p     = $this->product();          // isi 0, kosong 200
        $owner = $this->owner();

        $delivery = $this->actingAs($owner)
            ->post(route('inventory.storeDelivery'), $this->deliveryPayload($p, 100));
        $delivery->assertSessionHasNoErrors();
        $this->assertSame(100, (int) $p->fresh()->stock_filled);   // 100 / 100

        // Refill 40: isi 60, kosong 140 (total tetap 200)
        $this->actingAs($owner)->postJson(route('pos.store'), [
            'items'          => [['product_id' => $p->id, 'transaction_type' => 'refill', 'qty' => 40]],
            'payment_method' => 'cash', 'payment_status' => 'lunas',
        ])->assertOk();

        $deliveryId = \App\Models\AgentDelivery::latest('id')->first()->id;
        $this->actingAs($owner)->delete(route('inventory.destroyDelivery', $deliveryId))
            ->assertSessionHas('success');

        $p->refresh();
        // Sebelum fix: isi di-clamp ke 0 tapi kosong +100 penuh -> total 240 (fiktif).
        $this->assertSame(0, (int) $p->stock_filled);
        $this->assertSame(200, (int) $p->stock_empty);
        $this->assertSame(200, $p->total_stock);
        $this->assertTrue(DeletionLog::where('deletable_type', 'AgentDelivery')->exists());
    }

    public function test_pengguna_dengan_jejak_audit_tidak_bisa_dihapus(): void
    {
        $penggunaTarget = $this->owner('target@test.local', 'Owner Target');
        $pelanggan      = Customer::create(['name' => 'Bu Amin', 'customer_type' => 'rumah_tangga']);

        // Target pernah menghapus pelanggan -> punya catatan audit
        $this->actingAs($penggunaTarget)->delete(route('customers.destroy', $pelanggan))
            ->assertSessionHas('success');
        $this->assertTrue($penggunaTarget->deletionLogs()->exists());

        // Pemilik lain mencoba menghapusnya -> ditolak agar jejak audit utuh
        $this->actingAs($this->owner())->delete(route('users.destroy', $penggunaTarget))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $penggunaTarget->id]);
    }

    public function test_akun_sendiri_tidak_bisa_dihapus(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->delete(route('users.destroy', $owner))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }

    public function test_password_pengguna_wajib_huruf_dan_angka_minimal_8(): void
    {
        $owner = $this->owner();

        $lemah = [
            'name' => 'Kasir Baru', 'email' => 'kasir1@test.local',
            'password' => 'rahasiaaaaa', 'password_confirmation' => 'rahasiaaaaa',
            'role' => 'kasir',
        ];
        $this->actingAs($owner)->post(route('users.store'), $lemah)
            ->assertSessionHasErrors('password');

        $lemahPendek = array_merge($lemah, ['email' => 'kasir2@test.local', 'password' => 'abc1', 'password_confirmation' => 'abc1']);
        $this->actingAs($owner)->post(route('users.store'), $lemahPendek)
            ->assertSessionHasErrors('password');

        $kuat = array_merge($lemah, ['email' => 'kasir3@test.local', 'password' => 'password123', 'password_confirmation' => 'password123']);
        $this->actingAs($owner)->post(route('users.store'), $kuat)
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'kasir3@test.local']);
    }

    public function test_laporan_menolak_parameter_tanggal_tidak_valid(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->getJson(route('reports.index', [
            'week_start' => 'bukan-tanggal', 'week_end' => '2026-10-01',
        ]))->assertStatus(422)->assertJsonValidationErrors(['week_start']);

        $this->actingAs($owner)->getJson(route('reports.index', [
            'week_start' => '2026-10-10', 'week_end' => '2026-10-01',
        ]))->assertStatus(422)->assertJsonValidationErrors(['week_end']);

        $this->actingAs($owner)->getJson(route('reports.index', ['type' => 'evil']))
            ->assertStatus(422)->assertJsonValidationErrors(['type']);
    }

    public function test_header_keamanan_terpasang_di_semua_respons(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("frame-ancestors 'self'", (string) $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("form-action 'self'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_kasir_tidak_melihat_kolom_harga_beli_pada_halaman_penerimaan(): void
    {
        $kasir = User::firstOrCreate(
            ['email' => 'kasir@test.local'],
            ['name' => 'Kasir Uji', 'password' => 'secret123', 'role' => 'kasir']
        );
        $p = $this->product();
        \App\Models\AgentDelivery::create([
            'user_id' => $kasir->id,
            'product_id' => $p->id,
            'delivery_date' => today(),
            'qty_received' => 10,
            'buy_price_per_unit' => 14500,
            'total_cost' => 145000,
            'admin_fee' => 0,
        ]);

        $response = $this->actingAs($kasir)->get(route('inventory.deliveries'));

        $response->assertOk();
        $response->assertDontSee('Harga beli');
        $response->assertDontSee('Total biaya');
        $response->assertDontSee('data-last-price', false);
    }

    public function test_penanganan_error_database_tidak_membocorkan_sql_ke_layar(): void
    {
        \Illuminate\Support\Facades\Route::get('/test-db-error-trigger', function () {
            throw new \Illuminate\Database\QueryException('mysql', 'SELECT * FROM secret_table_not_found', [], new \Exception('Table not found'));
        });

        // Request web biasa (GET): harus render 500 error view tanpa membocorkan SQL
        $webResponse = $this->get('/test-db-error-trigger');
        $webResponse->assertStatus(500);
        $webResponse->assertDontSee('SELECT * FROM secret_table_not_found');
        $webResponse->assertSee('Terjadi kesalahan pada sistem');

        // Request AJAX / JSON: harus kirim JSON 500 tanpa stack trace
        $jsonResponse = $this->getJson('/test-db-error-trigger');
        $jsonResponse->assertStatus(500);
        $jsonResponse->assertJson(['success' => false]);
        $jsonResponse->assertDontSee('SELECT * FROM secret_table_not_found');
    }
}

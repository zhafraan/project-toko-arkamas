<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji jejak audit penyesuaian stok: wajib alasan, rekam sebelum→sesudah
 * beserta petugas, guard hapus pengguna, dan akses halaman riwayat (owner only).
 * Jalankan: php artisan test --filter=StockAdjustmentTest
 */
class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private function owner(string $email = 'owner@test.local', string $name = 'Owner Uji'): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => 'secret123', 'role' => 'owner']
        );
    }

    private function kasir(): User
    {
        return User::firstOrCreate(
            ['email' => 'kasir@test.local'],
            ['name' => 'Kasir Uji', 'password' => 'secret123', 'role' => 'kasir']
        );
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'LPG 3 Kg', 'sku' => 'LPG-03',
            'price_buy' => 14500, 'price_sell' => 18000, 'price_tube' => 150000,
            'stock_filled' => 10, 'stock_empty' => 5, 'stock_borrowed' => 1, 'is_active' => true,
        ]);
    }

    private function payload(array $override = []): array
    {
        return $override + [
            'stock_filled'   => 25,
            'stock_empty'    => 7,
            'stock_borrowed' => 3,
            'reason'         => 'Hasil stok opname mingguan',
        ];
    }

    public function test_penyesuaian_stok_wajib_mengisi_alasan(): void
    {
        $p = $this->product();
        $payload = $this->payload();
        unset($payload['reason']);

        $this->actingAs($this->owner())->post(route('inventory.adjustStock', $p), $payload)
            ->assertSessionHasErrors('reason');

        $this->assertSame(0, StockAdjustment::count());
        $p->refresh();
        $this->assertSame(10, (int) $p->stock_filled);
    }

    public function test_penyesuaian_stok_merekam_sebelum_sesudah_dan_petugas(): void
    {
        $p     = $this->product();
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('inventory.adjustStock', $p), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $p->refresh();
        $this->assertSame(25, (int) $p->stock_filled);
        $this->assertSame(7, (int) $p->stock_empty);
        $this->assertSame(3, (int) $p->stock_borrowed);

        $adj = StockAdjustment::sole();
        $this->assertSame($p->id, (int) $adj->product_id);
        $this->assertSame($owner->id, (int) $adj->user_id);
        $this->assertSame(10, $adj->filled_before);
        $this->assertSame(25, $adj->filled_after);
        $this->assertSame(5, $adj->empty_before);
        $this->assertSame(7, $adj->empty_after);
        $this->assertSame(1, $adj->borrowed_before);
        $this->assertSame(3, $adj->borrowed_after);
        $this->assertSame('Hasil stok opname mingguan', $adj->reason);
        $this->assertTrue($adj->hasChange());
        $this->assertStringContainsString('isi +15', $adj->diff_label);
    }

    public function test_penyesuaian_tanpa_perubahan_angka_tidak_mencatat_jejak(): void
    {
        $p = $this->product(); // 10 / 5 / 1

        $this->actingAs($this->owner())->post(route('inventory.adjustStock', $p), $this->payload([
            'stock_filled'   => 10,
            'stock_empty'    => 5,
            'stock_borrowed' => 1,
        ]))->assertSessionHas('info');

        $this->assertSame(0, StockAdjustment::count());
    }

    public function test_pengguna_pembuat_penyesuaian_tidak_bisa_dihapus(): void
    {
        $p          = $this->product();
        $pelaku     = $this->owner('pelaku@test.local', 'Owner Pelaku');
        $penghapus  = $this->owner('pemilik@test.local', 'Owner Pemilik');

        $this->actingAs($pelaku)->post(route('inventory.adjustStock', $p), $this->payload())
            ->assertSessionHasNoErrors();
        $this->assertTrue($pelaku->stockAdjustments()->exists());

        $this->actingAs($penghapus)->delete(route('users.destroy', $pelaku))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $pelaku->id]);
    }

    public function test_halaman_riwayat_penyesuaian_khusus_owner(): void
    {
        $p     = $this->product();
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('inventory.adjustStock', $p), $this->payload())
            ->assertSessionHasNoErrors();

        $this->actingAs($this->kasir())->get(route('deletion-logs.adjustments'))
            ->assertForbidden();

        $this->actingAs($owner)->get(route('deletion-logs.adjustments'))
            ->assertOk()
            ->assertSee('Hasil stok opname mingguan')
            ->assertSee('Penyesuaian Stok');
    }
}

<?php

namespace Database\Seeders;

use App\Models\AgentDelivery;
use App\Models\Customer;
use App\Models\DebtPayment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TubeLoan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DummyTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('role', 'owner')->first();
        $kasir = User::where('role', 'kasir')->first();
        $user = $kasir ?: $owner;

        $p3kg = Product::where('sku', 'LPG-03')->first();
        $p12kg = Product::where('sku', 'LPG-12')->first();
        $pBright = Product::where('sku', 'BG-55')->first();

        $cSri = Customer::where('name', 'like', '%Sri Wahyuni%')->first();
        $cBudi = Customer::where('name', 'like', '%Budi Santoso%')->first();
        $cToko = Customer::where('name', 'like', '%Berdikari%')->first();

        // 1. Catat kiriman agen sebelumnya
        if ($p3kg && !AgentDelivery::exists()) {
            AgentDelivery::create([
                'user_id' => $owner ? $owner->id : $user->id,
                'product_id' => $p3kg->id,
                'delivery_date' => now()->subDays(2),
                'qty_received' => 100,
                'buy_price_per_unit' => $p3kg->price_buy,
                'total_cost' => 100 * $p3kg->price_buy,
                'notes' => 'DO/2026/LPG/0981 - Alokasi agen mingguan',
            ]);
        }

        // 2. Transaksi 1: Tunai Lunas (Refill 3 tabung 3kg oleh Ibu Sri)
        if ($p3kg && $cSri && !Transaction::where('invoice_number', 'TRX-20261015-0001')->exists()) {
            $t1 = Transaction::create([
                'invoice_number' => 'TRX-20261015-0001',
                'user_id' => $user->id,
                'customer_id' => $cSri->id,
                'transaction_date' => now()->subHours(5),
                'total_amount' => 54000,
                'paid_amount' => 60000,
                'remaining_debt' => 0,
                'change_amount' => 6000,
                'payment_status' => 'lunas',
                'payment_method' => 'cash',
                'payment_note' => 'Tunai di kasir',
                'paid_at' => now()->subHours(5),
            ]);

            TransactionDetail::create([
                'transaction_id' => $t1->id,
                'product_id' => $p3kg->id,
                'transaction_type' => 'refill',
                'qty' => 3,
                'price' => 18000,
                'cost_price' => 14500,
                'subtotal' => 54000,
            ]);
        }

        // 3. Transaksi 2: Pinjam Tabung + Belum Lunas (Kasbon) oleh Pak Budi (Warung)
        if ($p3kg && $cBudi && !Transaction::where('invoice_number', 'TRX-20261015-0002')->exists()) {
            $t2 = Transaction::create([
                'invoice_number' => 'TRX-20261015-0002',
                'user_id' => $user->id,
                'customer_id' => $cBudi->id,
                'transaction_date' => now()->subHours(3),
                'total_amount' => 90000,
                'paid_amount' => 40000,
                'remaining_debt' => 50000,
                'change_amount' => 0,
                'payment_status' => 'belum_lunas',
                'payment_method' => 'cash',
                'payment_note' => 'DP Rp 40.000, sisa tempo 3 hari',
                'paid_at' => null,
            ]);

            TransactionDetail::create([
                'transaction_id' => $t2->id,
                'product_id' => $p3kg->id,
                'transaction_type' => 'pinjam_tabung',
                'qty' => 5,
                'price' => 18000,
                'cost_price' => 14500,
                'subtotal' => 90000,
            ]);

            TubeLoan::create([
                'transaction_id' => $t2->id,
                'customer_id' => $cBudi->id,
                'product_id' => $p3kg->id,
                'qty_borrowed' => 5,
                'qty_returned' => 2,
                'deposit_amount' => 0,
                'status' => 'sebagian_kembali',
                'borrow_date' => now()->subDays(1),
                'notes' => 'Pinjam untuk hajatan & warung, 2 tabung sudah dikembalikan',
            ]);
        }

        // 4. Transaksi 3: Beli Tabung Baru (Plus Tabung 12Kg) via Transfer QRIS
        if ($p12kg && $cToko && !Transaction::where('invoice_number', 'TRX-20261015-0003')->exists()) {
            $t3 = Transaction::create([
                'invoice_number' => 'TRX-20261015-0003',
                'user_id' => $user->id,
                'customer_id' => $cToko->id,
                'transaction_date' => now()->subHours(1),
                'total_amount' => 718000,
                'paid_amount' => 718000,
                'remaining_debt' => 0,
                'change_amount' => 0,
                'payment_status' => 'lunas',
                'payment_method' => 'transfer',
                'payment_note' => 'BCA QRIS REF#998124',
                'paid_at' => now()->subHours(1),
            ]);

            TransactionDetail::create([
                'transaction_id' => $t3->id,
                'product_id' => $p12kg->id,
                'transaction_type' => 'plus_tabung',
                'qty' => 1,
                'price' => 718000, // 168000 + 550000
                'cost_price' => 145000,
                'subtotal' => 718000,
            ]);
        }
    }
}

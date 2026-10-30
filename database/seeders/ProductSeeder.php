<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name'           => 'LPG 3 Kg (Subsidi)',
                'sku'            => 'LPG-03',
                'price_buy'      => 14500,
                'price_sell'     => 18000,
                'price_tube'     => 150000,
                'stock_filled'   => 50,
                'stock_empty'    => 20,
                'stock_borrowed' => 5,
            ],
            [
                'name'           => 'Bright Gas 5.5 Kg',
                'sku'            => 'BG-55',
                'price_buy'      => 68000,
                'price_sell'     => 78000,
                'price_tube'     => 300000,
                'stock_filled'   => 30,
                'stock_empty'    => 10,
                'stock_borrowed' => 2,
            ],
            [
                'name'           => 'LPG 12 Kg (Non-Subsidi)',
                'sku'            => 'LPG-12',
                'price_buy'      => 145000,
                'price_sell'     => 168000,
                'price_tube'     => 550000,
                'stock_filled'   => 20,
                'stock_empty'    => 8,
                'stock_borrowed' => 1,
            ],
            [
                'name'           => 'LPG 50 Kg (Industri)',
                'sku'            => 'LPG-50',
                'price_buy'      => 580000,
                'price_sell'     => 650000,
                'price_tube'     => 2500000,
                'stock_filled'   => 5,
                'stock_empty'    => 3,
                'stock_borrowed' => 0,
            ],
            [
                'name'           => 'Telur Ayam (kg)',
                'sku'            => 'TELUR-KG',
                'price_buy'      => 25000,
                'price_sell'     => 32000,
                'stock_filled'   => 100.0,
                'stock_crates'   => 10.0,
                'category'       => 'telur',
                'unit'           => 'kg',
            ],
            [
                'name'           => 'Telur Ayam (Tray 30 butir)',
                'sku'            => 'TELUR-TRAY',
                'price_buy'      => 750000,
                'price_sell'     => 850000,
                'stock_filled'   => 20.0,
                'category'       => 'telur',
                'unit'           => 'tray',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}

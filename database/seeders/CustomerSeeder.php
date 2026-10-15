<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name'          => 'Ibu Sri Wahyuni',
                'phone'         => '081234567890',
                'nik'           => '3201015501800001',
                'customer_type' => 'rumah_tangga',
                'address'       => 'Jl. Mawar No. 12, RT 02/04',
            ],
            [
                'name'          => 'Pak Budi Santoso (Warung Berkah)',
                'phone'         => '081398765432',
                'nik'           => '3201011204750002',
                'customer_type' => 'umkm',
                'address'       => 'Jl. Raya Cinere No. 45, Ruko A2',
            ],
            [
                'name'          => 'Toko Kelontong Berdikari',
                'phone'         => '082155443322',
                'nik'           => '3201012308820003',
                'customer_type' => 'pengecer',
                'address'       => 'Pasar Segar Blok C No. 8',
            ],
            [
                'name'          => 'Resto Ayam Bakar Mantap',
                'phone'         => '085711223344',
                'nik'           => '3201016709850004',
                'customer_type' => 'umkm',
                'address'       => 'Jl. Margonda Raya No. 108',
            ],
            [
                'name'          => 'H. Ahmad Fauzi',
                'phone'         => '081809988776',
                'nik'           => '3201010101700005',
                'customer_type' => 'rumah_tangga',
                'address'       => 'Komplek Permata Hijau B3/14',
            ],
        ];

        foreach ($customers as $c) {
            Customer::firstOrCreate(['name' => $c['name']], $c);
        }
    }
}

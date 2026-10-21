<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Pemilik Toko',
            'email'    => 'owner@arkamas.com',
            'password' => Hash::make('owner001'),
            'role'     => 'owner',
        ]);

        User::create([
            'name'     => 'Admin Toko',
            'email'    => 'admin@arkamas.com',
            'password' => Hash::make('admin001'),
            'role'     => 'admin',
        ]);

        User::create([
            'name'     => 'Kasir 1',
            'email'    => 'kasir@arkamas.com',
            'password' => Hash::make('kasir001'),
            'role'     => 'kasir',
        ]);
    }
}

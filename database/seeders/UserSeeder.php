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
            'password' => Hash::make('password'),
            'role'     => 'owner',
        ]);

        User::create([
            'name'     => 'Kasir 1',
            'email'    => 'kasir@arkamas.com',
            'password' => Hash::make('password'),
            'role'     => 'kasir',
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // PENTING: Ganti password ini dengan password yang kuat sebelum deploy!
        User::create([
            'name'     => 'Pemilik Toko',
            'email'    => 'owner@arkamas.com',
            'password' => Hash::make(env('OWNER_DEFAULT_PASSWORD', 'GantiPasswordIni!2026')),
            'role'     => 'owner',
        ]);

        User::create([
            'name'     => 'Kasir 1',
            'email'    => 'kasir@arkamas.com',
            'password' => Hash::make(env('KASIR_DEFAULT_PASSWORD', 'GantiPasswordIni!2026')),
            'role'     => 'kasir',
        ]);
    }
}

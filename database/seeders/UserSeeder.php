<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'registration_status' => 'verified',
            'phone' => '08111111111'
        ]);

        // 2. Bendahara
        User::create([
            'name' => 'Bendahara RT',
            'email' => 'bendahara@example.com',
            'password' => Hash::make('password'),
            'role' => 'treasurer',
            'registration_status' => 'verified',
            'phone' => '08222222222'
        ]);
    }
}

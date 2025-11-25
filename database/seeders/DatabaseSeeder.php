<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MasterDataSeeder::class, // Jalankan ini duluan (Master Data)
            UserSeeder::class,       // Akun Admin/Bendahara
            UserFullSeeder::class,   // Data Warga Dummy
        ]);
    }
}

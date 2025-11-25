<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\House;
use App\Models\Family;
use App\Models\Citizen;
use App\Models\Billing;
use App\Models\Activity;
use App\Models\Announcement;
use App\Models\DuesType;
use Illuminate\Support\Facades\Hash;

class UserFullSeeder extends Seeder
{
    public function run(): void
    {
        // ================================
        // 1. Buat User (Warga)
        // ================================
        $user = User::create([
            'name' => 'John Resident',
            'email' => 'warga@example.com',
            'phone' => '08123456789',
            'password' => Hash::make('password123'),
            'role' => 'resident',
            'registration_status' => 'verified',
        ]);

        // ================================
        // 2. Buat Rumah
        // ================================
        $house = House::create([
            'house_name' => 'Blok A No. 10',
            'owner_name' => 'Bapak John Doe',
            'address' => 'Jl. Mawar No. 10, Jakarta',
            'house_type' => 'Type 36',
            'has_complete_facilities' => true,
            'status' => 'occupied'
        ]);

        // ================================
        // 3. Buat Keluarga (KK)
        // ================================
        $family = Family::create([
            'house_id' => $house->id,
            'kk_number' => '3201012345670001',
            'ownership_status' => 'owner', // Kolom baru
            'status' => 'active'
        ]);

        // ================================
        // 4. Buat Warga (Citizen)
        // ================================
        // Kepala Keluarga (Connect ke User)
        Citizen::create([
            'user_id' => $user->id,
            'family_id' => $family->id,
            'nik' => '3201011234560001',
            'name' => 'John Resident',
            'phone' => '08123456789',
            'gender' => 'male',
            'birth_place' => 'Jakarta',
            'birth_date' => '1990-01-01',
            'religion' => 'Islam',
            'blood_type' => 'O',
            'family_role' => 'Kepala Keluarga',
            'status' => 'permanent'
        ]);

        // Istri (Tanpa User)
        Citizen::create([
            'user_id' => null,
            'family_id' => $family->id,
            'nik' => '3201011234560002',
            'name' => 'Jane Resident',
            'phone' => '08129999999',
            'gender' => 'female',
            'birth_place' => 'Bandung',
            'birth_date' => '1992-05-10',
            'family_role' => 'Istri',
            'status' => 'permanent'
        ]);

        // ================================
        // 5. BONUS: Dummy Data Fitur Lain
        // ================================

        // Buat Tagihan Iuran Sampah untuk Keluarga ini
        $duesType = DuesType::first(); // Ambil tipe iuran pertama
        if ($duesType) {
            Billing::create([
                'family_id' => $family->id,
                'dues_type_id' => $duesType->id,
                'billing_code' => 'INV-'.time(),
                'period' => 'Oktober 2025',
                'amount' => $duesType->amount,
                'status' => 'unpaid',
            ]);
        }

        // Buat Pengumuman
        Announcement::create([
            'user_id' => 1, // Asumsi user ID 1 adalah Admin
            'title' => 'Kerja Bakti Minggu Ini',
            'content' => 'Diharapkan seluruh warga berkumpul di lapangan jam 7 pagi.',
        ]);

        // Buat Kegiatan
        Activity::create([
            'name' => 'Senam Pagi Bersama',
            'category' => 'Olahraga',
            'activity_date' => now()->addDays(3),
            'location' => 'Lapangan Utama',
            'person_in_charge' => 'Pak RT',
            'status' => 'upcoming'
        ]);
    }
}

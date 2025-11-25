<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentChannel;
use App\Models\DuesType;
use App\Models\TransactionCategory;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Data Bank / E-Wallet
        PaymentChannel::create([
            'channel_name' => 'Bank BCA',
            'type' => 'bank',
            'account_number' => '1234567890',
            'account_name' => 'RT 05 RW 03',
            'notes' => 'Harap konfirmasi setelah transfer',
            'is_active' => true,
        ]);

        PaymentChannel::create([
            'channel_name' => 'Gopay',
            'type' => 'e-wallet',
            'account_number' => '081234567890',
            'account_name' => 'Bendahara RT',
            'is_active' => true,
        ]);

        // 2. Jenis Iuran Wajib
        DuesType::create(['name' => 'Iuran Kebersihan', 'amount' => 25000]);
        DuesType::create(['name' => 'Iuran Keamanan', 'amount' => 50000]);
        DuesType::create(['name' => 'Dana Sosial', 'amount' => 10000]);

        // 3. Kategori Pemasukan/Pengeluaran
        TransactionCategory::create(['name' => 'Listrik Pos Satpam', 'type' => 'expense']);
        TransactionCategory::create(['name' => 'Perbaikan Jalan', 'type' => 'expense']);
        TransactionCategory::create(['name' => 'Sumbangan Donatur', 'type' => 'income']);
        TransactionCategory::create(['name' => 'Iuran Warga', 'type' => 'income']);
    }
}

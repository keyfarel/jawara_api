<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. PAYMENT CHANNELS
        Schema::create('payment_channels', function (Blueprint $table) {
            $table->id();
            $table->string('channel_name'); // ex: Bank BCA
            $table->string('type'); // Bank, E-Wallet
            $table->string('account_number');
            $table->string('account_name'); // a/n
            $table->string('thumbnail')->nullable();
            $table->string('qr_code')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. DUES TYPES (Master Data Jenis Iuran)
        Schema::create('dues_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ex: Iuran Sampah
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        // 3. BILLINGS (Tagihan ke Keluarga)
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->onDelete('cascade');
            $table->foreignId('dues_type_id')->constrained('dues_types');
            $table->string('billing_code')->unique();
            $table->string('period'); // ex: "Oktober 2025"
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['unpaid', 'paid', 'cancelled'])->default('unpaid');
            $table->timestamps();
        });

        // 4. TRANSACTION CATEGORIES
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ex: Listrik, Sumbangan
            $table->enum('type', ['income', 'expense']);
            $table->timestamps();
        });

        // 5. TRANSACTIONS
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('transaction_category_id')->nullable()->constrained('transaction_categories');
            $table->foreignId('billing_id')->nullable()->constrained('billings'); // Jika bayar tagihan

            $table->string('title');
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->text('description')->nullable();
            $table->string('proof_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('transaction_categories');
        Schema::dropIfExists('billings');
        Schema::dropIfExists('dues_types');
        Schema::dropIfExists('payment_channels');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. MUTATIONS (Mutasi Warga)
        Schema::create('mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->onDelete('cascade');
            // Menyimpan riwayat rumah mana saat mutasi terjadi
            $table->foreignId('house_id')->nullable()->constrained('houses');
            $table->enum('mutation_type', ['move_in', 'move_out', 'deceased']);
            $table->date('mutation_date');
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // 2. CITIZEN MESSAGES (Aspirasi)
        Schema::create('citizen_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['pending', 'processed', 'completed', 'rejected'])->default('pending');
            $table->timestamps();
        });

        // 3. ACTIVITIES (Kegiatan)
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable(); // Kerja Bakti, Rapat
            $table->dateTime('activity_date');
            $table->string('location')->nullable();
            $table->string('person_in_charge')->nullable(); // Penanggung Jawab
            $table->text('description')->nullable();
            $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])->default('upcoming');
            $table->timestamps();
        });

        // 4. ANNOUNCEMENTS (Broadcast)
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // Admin yg posting
            $table->string('title');
            $table->text('content');
            $table->string('image_url')->nullable();
            $table->string('document_url')->nullable();
            $table->timestamps();
        });

        // 5. ACTIVITY LOGS
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // ex: "Created Citizen"
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('citizen_messages');
        Schema::dropIfExists('mutations');
    }
};

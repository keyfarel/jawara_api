<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_id')->nullable()->constrained('houses')->nullOnDelete();
            $table->string('kk_number')->nullable()->unique(); // Nomor Kartu Keluarga
            $table->enum('ownership_status', ['owner', 'renter'])->default('owner'); // Status kepemilikan
            $table->string('status')->default('active'); // active, moved, archive
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};

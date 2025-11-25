<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->string('house_name')->nullable(); // Blok/No Rumah
            $table->string('owner_name')->nullable(); // Nama Pemilik
            $table->text('address');
            $table->string('house_type')->nullable(); // Type 36, 45
            $table->boolean('has_complete_facilities')->default(false);
            $table->string('status')->default('empty'); // occupied, empty
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('houses');
    }
};

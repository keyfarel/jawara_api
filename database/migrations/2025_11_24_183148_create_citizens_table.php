<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('nik')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->string('religion')->nullable();
            $table->string('blood_type', 3)->nullable();

            // Kolom yang tadi ketinggalan
            $table->string('id_card_photo')->nullable();

            $table->string('family_role');
            $table->string('education')->nullable();
            $table->string('occupation')->nullable();
            $table->string('status')->default('permanent');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizens');
    }
};

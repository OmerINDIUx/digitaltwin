<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Eliminamos si existe para asegurar la estructura de Laravel
        Schema::dropIfExists('reservations');
        
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('zone'); // Gimnasio, Alberca, Canchas
            $table->dateTime('reservation_date');
            $table->integer('guests')->default(1);
            $table->string('status')->default('pending'); // pending, confirmed, cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

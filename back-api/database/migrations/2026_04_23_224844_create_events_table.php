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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('zone'); // slug de la zona
            $table->dateTime('event_date');
            $table->integer('duration')->default(1);
            $table->integer('capacity');
            $table->integer('current_attendees')->default(0);
            $table->enum('type', ['class', 'maintenance', 'private_event', 'other'])->default('class');
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $box) {
            $box->id();
            $box->string('slug')->unique();
            $box->string('name');
            $box->string('icon')->default('activity');
            $box->string('image')->nullable();
            $box->text('description')->nullable();
            $box->integer('capacity')->default(0);
            $box->time('opening_hour')->default('08:00');
            $box->time('closing_hour')->default('20:00');
            $box->string('status')->default('active');
            $box->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};

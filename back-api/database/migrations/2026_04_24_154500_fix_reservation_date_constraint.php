<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Si existe la columna vieja, la hacemos opcional para que no bloquee
            if (Schema::hasColumn('reservations', 'reservation_date')) {
                $table->date('reservation_date')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
    }
};

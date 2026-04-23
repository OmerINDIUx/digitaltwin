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
        Schema::table('zones', function (Blueprint $table) {
            $table->integer('max_reservation_hours')->default(2)->after('capacity');
            $table->string('rest_days')->nullable()->after('max_reservation_hours'); // ej: "1,2" (Lunes, Martes)
            $table->time('rest_start_hour')->nullable()->after('rest_days');
            $table->time('rest_end_hour')->nullable()->after('rest_start_hour');
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn(['max_reservation_hours', 'rest_days', 'rest_start_hour', 'rest_end_hour']);
        });
    }
};

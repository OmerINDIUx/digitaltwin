<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'time')) {
                $table->string('time')->nullable()->after('date');
            }
            if (!Schema::hasColumn('reservations', 'guests')) {
                $table->integer('guests')->default(1)->after('time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['time', 'guests']);
        });
    }
};

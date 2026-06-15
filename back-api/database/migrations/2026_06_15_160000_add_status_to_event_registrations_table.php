<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('event_registrations', 'status')) {
            return;
        }

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('status')->default('por_pagar')->after('email');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('event_registrations', 'status')) {
            return;
        }

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

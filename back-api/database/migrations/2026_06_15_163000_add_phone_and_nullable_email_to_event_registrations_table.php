<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('event_registrations', 'phone')) {
            Schema::table('event_registrations', function (Blueprint $table) {
                $table->string('phone')->nullable()->after('email');
            });
        }

        DB::statement('ALTER TABLE event_registrations MODIFY email VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE event_registrations SET email = '' WHERE email IS NULL");
        DB::statement('ALTER TABLE event_registrations MODIFY email VARCHAR(255) NOT NULL');

        if (Schema::hasColumn('event_registrations', 'phone')) {
            Schema::table('event_registrations', function (Blueprint $table) {
                $table->dropColumn('phone');
            });
        }
    }
};

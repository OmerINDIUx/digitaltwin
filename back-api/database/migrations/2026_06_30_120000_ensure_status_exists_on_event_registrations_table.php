<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('event_registrations') || Schema::hasColumn('event_registrations', 'status')) {
            return;
        }

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('status')->default('por_pagar')->after('email');
        });

        if (Schema::hasTable('events')) {
            DB::statement("
                UPDATE events
                SET current_attendees = (
                    SELECT COUNT(*)
                    FROM event_registrations
                    WHERE event_registrations.event_id = events.id
                    AND event_registrations.status IN ('aceptado', 'por_pagar', 'pagado')
                )
            ");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('event_registrations') || !Schema::hasColumn('event_registrations', 'status')) {
            return;
        }

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

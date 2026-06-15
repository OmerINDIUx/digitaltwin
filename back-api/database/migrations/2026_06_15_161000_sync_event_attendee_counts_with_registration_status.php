<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        DB::statement("
            UPDATE events
            SET current_attendees = (
                SELECT COUNT(*)
                FROM event_registrations
                WHERE event_registrations.event_id = events.id
            )
        ");
    }
};

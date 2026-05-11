<?php

namespace Database\Seeders;

use App\Models\Reservation;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class MexicanReservationsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. IMPORTAR RESERVACIONES LOCALES (SINCRO TOTAL)
        $jsonPath = database_path('data/local_reservations.json');
        
        if (file_exists($jsonPath)) {
            $localData = json_decode(file_get_contents($jsonPath), true);
            foreach ($localData as $row) {
                Reservation::updateOrCreate(
                    [
                        'name'             => $row['name'],
                        'zone'             => $row['zone'],
                        'reservation_date' => $row['reservation_date'],
                    ],
                    [
                        'email'    => $row['email'],
                        'phone'    => $row['phone'],
                        'guests'   => $row['guests'],
                        'duration' => $row['duration'] ?? 60,
                        'status'   => $row['status'],
                    ]
                );
            }
            echo "✅ ¡" . count($localData) . " reservaciones locales sincronizadas!\n";
        }

        // 2. GENERAR RESERVACIONES ADICIONALES PARA EL FUTURO
        $mexicanNames = [
            ['name' => 'Alejandro Villagómez', 'email' => 'a.villagomez@gmail.com'],
            ['name' => 'Beatriz Arizmendi', 'email' => 'betty.ariz@outlook.com'],
            ['name' => 'Cuauhtémoc Blanco Rojas', 'email' => 'cblanco.rojas@gmail.com'],
            ['name' => 'Dulce María Espinoza', 'email' => 'dulce.esp@hotmail.com'],
            ['name' => 'Efraín Juárez Luna', 'email' => 'ejuarez.luna@gmail.com'],
            ['name' => 'Florinda Meza García', 'email' => 'fmeza.garcia@gmail.com'],
            ['name' => 'Gerardo Esquivel', 'email' => 'g.esquivel@prodigy.net.mx'],
            ['name' => 'Humberto Zurita', 'email' => 'hzurita@gmail.com'],
            ['name' => 'Itzel Manzanero', 'email' => 'itzel.manza@gmail.com'],
            ['name' => 'Javier "Chicharito" Hernández', 'email' => 'jhernandez.ch@gmail.com'],
            ['name' => 'Karla Souza Olivares', 'email' => 'ksouza.oli@gmail.com'],
            ['name' => 'Lorenzo Córdova', 'email' => 'lorenzo.cordova@gmail.com'],
            ['name' => 'Marisol González', 'email' => 'marisol.gonz@gmail.com'],
            ['name' => 'Noé Ramos Suástegui', 'email' => 'n.ramos@hotmail.com'],
            ['name' => 'Oribe Peralta', 'email' => 'operalta@gmail.com'],
            ['name' => 'Paola Longoria', 'email' => 'paola.long@gmail.com'],
            ['name' => 'Quetzalcóatl Ruiz', 'email' => 'q.ruiz@gmail.com'],
            ['name' => 'Rogelio Funes Mori', 'email' => 'rfunes.m@gmail.com'],
            ['name' => 'Salma Hayek Jiménez', 'email' => 'salma.hayek@gmail.com'],
            ['name' => 'Tizoc Guerrero', 'email' => 'tizoc.g@gmail.com'],
            ['name' => 'Úrsula Pruneda', 'email' => 'ursula.p@gmail.com'],
            ['name' => 'Vicente Fernández Abarca', 'email' => 'v.fernandez@gmail.com'],
            ['name' => 'Wendy González', 'email' => 'wendy.gonz@gmail.com'],
            ['name' => 'Ximena Navarrete', 'email' => 'ximena.nav@gmail.com'],
            ['name' => 'Yalitza Aparicio', 'email' => 'yalitza.ap@gmail.com'],
            ['name' => 'Zuria Vega', 'email' => 'zuria.vega@gmail.com'],
        ];

        $zones = ['gym', 'pool', 'canchas'];
        $today = Carbon::today('America/Mexico_City');
        $endDate = $today->copy()->addDays(10); // Generar para los próximos 10 días

        echo "🧹 Limpiando reservaciones existentes desde " . $today->toDateString() . " hasta " . $endDate->toDateString() . " para volver a poblar...\n";
        Reservation::whereDate('reservation_date', '>=', $today)
                   ->whereDate('reservation_date', '<=', $endDate)
                   ->delete();

        echo "✍️ Generando reservaciones para el periodo " . $today->toDateString() . " al " . $endDate->toDateString() . "...\n";

        // Iterar desde hoy hasta la fecha fin
        for ($date = $today->copy(); $date->lte($endDate); $date->addDay()) {
            
            echo "📅 Generando datos para el " . $date->toDateString() . "...\n";

            // Generar entre 8 y 15 reservaciones aleatorias por día (un poco más denso)
            $numReservations = rand(8, 15);
            for ($i = 0; $i < $numReservations; $i++) {
                $person = $mexicanNames[array_rand($mexicanNames)];
                $hour = rand(7, 21); // Rango de horario extendido
                $minute = [0, 15, 30, 45][array_rand([0, 1, 2, 3])];
                
                $reservationTime = $date->copy()->setHour($hour)->setMinute($minute)->setSecond(0);

                Reservation::create([
                    'name'             => $person['name'],
                    'email'            => $person['email'],
                    'phone'            => '+52 55 ' . rand(1111, 9999) . ' ' . rand(1111, 9999),
                    'zone'             => $zones[array_rand($zones)],
                    'reservation_date' => $reservationTime,
                    'date'             => $date->toDateString(),
                    'time'             => $reservationTime->format('H:i'),
                    'guests'           => rand(1, 4),
                    'duration'         => 60,
                    'status'           => 'confirmed',
                ]);
            }
        }

        echo "✅ ¡Población completada con éxito hasta el " . $endDate->toDateString() . "!\n";
    }
}

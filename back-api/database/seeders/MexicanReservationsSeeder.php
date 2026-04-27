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

        // Generar algunas extras para hoy y mañana
        for ($i = 0; $i < 2; $i++) {
            $currentDate = $today->copy()->addDays($i);
            for ($hour = 8; $hour <= 20; $hour += 2) {
                $person = $mexicanNames[array_rand($mexicanNames)];
                Reservation::updateOrCreate(
                    [
                        'name'             => $person['name'],
                        'zone'             => $zones[array_rand($zones)],
                        'reservation_date' => $currentDate->copy()->setHour($hour)->setMinute(0),
                    ],
                    [
                        'email'    => $person['email'],
                        'phone'    => '+52 55 ' . rand(1111, 9999) . ' ' . rand(1111, 9999),
                        'guests'   => rand(1, 3),
                        'duration' => 60,
                        'status'   => 'confirmed',
                    ]
                );
            }
        }

        echo "✅ ¡Sincronización y generación completada!\n";
    }
}

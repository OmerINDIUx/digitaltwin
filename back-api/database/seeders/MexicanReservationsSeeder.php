<?php

namespace Database\Seeders;

use App\Models\Reservation;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class MexicanReservationsSeeder extends Seeder
{
    public function run(): void
    {
        // Lista extendida de nombres mexicanos realistas
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

        // Generar para los próximos 3 días
        for ($i = 0; $i < 3; $i++) {
            $currentDate = $today->copy()->addDays($i);
            
            // Desde las 7:00 AM hasta las 9:00 PM (21:00)
            for ($hour = 7; $hour <= 21; $hour++) {
                
                // Generar entre 1 y 3 reservas por cada hora
                $reservationsInHour = rand(1, 3);
                
                for ($j = 0; $j < $reservationsInHour; $j++) {
                    $person = $mexicanNames[array_rand($mexicanNames)];
                    $minute = [0, 15, 30, 45][rand(0, 3)];
                    
                    Reservation::create([
                        'name'             => $person['name'],
                        'email'            => $person['email'],
                        'phone'            => '+52 55 ' . rand(1111, 9999) . ' ' . rand(1111, 9999),
                        'zone'             => $zones[array_rand($zones)],
                        'reservation_date' => $currentDate->copy()->setHour($hour)->setMinute($minute),
                        'guests'           => rand(1, 4),
                        'duration'         => 60,
                        'status'           => $i === 0 && $hour <= Carbon::now()->hour ? 'confirmed' : 'pending',
                    ]);
                }
            }
        }

        echo "✅ ¡Base de datos poblada con éxito con nombres mexicanos!\n";
    }
}

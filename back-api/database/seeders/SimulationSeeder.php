<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reservation;
use Carbon\Carbon;

class SimulationSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar datos previos si lo deseas (Opcional)
        // Reservation::truncate();

        $zones = ['gym', 'pool', 'canchas'];
        $names = ['Juan Perez', 'Maria Garcia', 'Carlos Lopez', 'Ana Martinez', 'Roberto Gomez', 'Elena Rodriguez', 'Diego Sanchez', 'Lucia Fernandez'];

        // Generar datos para los últimos 7 días
        for ($i = 0; $i <= 7; $i++) {
            $date = Carbon::now()->subDays($i);
            
            // Generar entre 5 y 15 reservas por día
            $reservationsPerDay = rand(8, 18);

            for ($j = 0; $j < $reservationsPerDay; $j++) {
                $zone = $zones[array_rand($zones)];
                
                // Horarios realistas entre 7am y 9pm
                $hour = rand(7, 21);
                $minute = [0, 30][array_rand([0, 30])];
                $time = sprintf('%02d:%02d', $hour, $minute);

                $guests = rand(1, 5);
                
                // Simular asistencia (80% de probabilidad de que asistieron si es del pasado)
                $checkedIn = null;
                if ($i > 0 && rand(1, 100) <= 85) {
                    $checkedIn = $date->copy()->setTime($hour, $minute)->addMinutes(rand(1, 15));
                }

                Reservation::create([
                    'name' => $names[array_rand($names)],
                    'zone' => $zone,
                    'date' => $date->format('Y-m-d'),
                    'time' => $time,
                    'guests' => $guests,
                    'checked_in_at' => $checkedIn,
                ]);
            }
        }

        echo "✅ Simulación de 7 días completada con éxito.\n";
    }
}

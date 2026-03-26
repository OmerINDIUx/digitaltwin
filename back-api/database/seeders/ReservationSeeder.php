<?php

namespace Database\Seeders;

use App\Models\Reservation;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar para empezar de cero
        Reservation::truncate();

        $zones = ['gym', 'pool', 'canchas'];
        
        // Generar para los próximos 7 días
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::today()->addDays($i);
            
            // 5 a 8 reservaciones aleatorias por día
            $numRes = rand(5, 8);
            
            for ($j = 0; $j < $numRes; $j++) {
                // Horas entre las 7:00 y las 21:00
                $hour = rand(7, 21);
                $minute = rand(0, 59);
                
                Reservation::create([
                    'zone' => $zones[array_rand($zones)],
                    'reservation_date' => $date->copy()->hour($hour)->minute($minute),
                    'guests' => rand(1, 4),
                    'status' => 'confirmed'
                ]);
            }
        }
        
        // Agregar algunas reservas en LA HORA ACTUAL para ver el cambio inmediato en el 3D
        foreach ($zones as $zone) {
            Reservation::create([
                'zone' => $zone,
                'reservation_date' => now()->subMinutes(15), // Un poco antes para que "estén" allí
                'guests' => rand(5, 12),
                'status' => 'confirmed'
            ]);
        }
    }
}

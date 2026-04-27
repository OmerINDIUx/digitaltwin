<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('data/local_zones.json');

        if (file_exists($jsonPath)) {
            $zonesData = json_decode(file_get_contents($jsonPath), true);
            foreach ($zonesData as $row) {
                Zone::updateOrCreate(
                    ['slug' => $row['slug']],
                    [
                        'name'                   => $row['name'],
                        'icon'                   => $row['icon'],
                        'description'            => $row['description'],
                        'capacity'               => $row['capacity'],
                        'opening_hour'           => $row['opening_hour'],
                        'closing_hour'           => $row['closing_hour'],
                        'image'                  => $row['image'],
                        'status'                 => $row['status'] ?? 'active',
                        'max_reservation_hours'  => $row['max_reservation_hours'] ?? 2,
                        'schedules'              => $row['schedules'], // Aquí va la matriz semanal
                    ]
                );
            }
            echo "✅ ¡Configuración de Áreas sincronizada con éxito!\n";
        } else {
            echo "⚠️ No se encontró el archivo de zonas locales.\n";
        }
    }
}

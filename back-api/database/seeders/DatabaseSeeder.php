<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
        // Sincronizar Áreas y Reservaciones
        $this->call([
            ZoneSeeder::class,
            MexicanReservationsSeeder::class,
        ]);
    }
}

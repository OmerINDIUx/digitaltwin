<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        Zone::updateOrCreate(['slug' => 'gym'], [
            'name' => 'Gimnasio',
            'icon' => 'activity',
            'description' => 'Área de pesas y cardio de alto rendimiento.',
            'capacity' => 50,
            'opening_hour' => '07:00',
            'closing_hour' => '22:00',
            'image' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&q=80&w=400',
        ]);

        Zone::updateOrCreate(['slug' => 'pool'], [
            'name' => 'Natación',
            'icon' => 'waves',
            'description' => 'Centro acuático con alberca climatizada.',
            'capacity' => 30,
            'opening_hour' => '08:00',
            'closing_hour' => '20:00',
            'image' => 'Natación.JPG',
        ]);

        Zone::updateOrCreate(['slug' => 'canchas'], [
            'name' => 'Canchas Deportivas',
            'icon' => 'target',
            'description' => 'Canchas multideportivas para fútbol y básquet.',
            'capacity' => 20,
            'opening_hour' => '09:00',
            'closing_hour' => '21:00',
            'image' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&q=80&w=400',
        ]);
    }
}

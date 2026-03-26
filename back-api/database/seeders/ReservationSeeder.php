<?php

namespace Database\Seeders;

use App\Models\Reservation;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        // Primero actualizar los registros que ya existen sin nombre
        $mexicanNames = $this->getNames();
        $existing = Reservation::whereNull('name')->orWhere('name', '')->get();
        foreach ($existing as $r) {
            $person = $mexicanNames[array_rand($mexicanNames)];
            $r->update([
                'name'  => $person['name'],
                'email' => $person['email'],
                'phone' => $person['phone'],
            ]);
        }

        // ── RESERVAS EN LA HORA ACTUAL (para que el panel EN VIVO muestre datos) ──
        $nowTime = Carbon::now('America/Mexico_City');
        foreach (['gym', 'pool', 'canchas'] as $zone) {
            $person = $mexicanNames[array_rand($mexicanNames)];
            Reservation::create([
                'name'             => $person['name'],
                'email'            => $person['email'],
                'phone'            => $person['phone'],
                'zone'             => $zone,
                'reservation_date' => $nowTime->copy()->subMinutes(rand(5, 30)),
                'guests'           => rand(2, 6),
                'status'           => 'confirmed',
            ]);
        }

        // ── 7 DÍAS DE RESERVACIONES ──
        $zones = ['gym', 'pool', 'canchas'];

        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::today('America/Mexico_City')->addDays($i);
            $numRes = rand(6, 10);

            for ($j = 0; $j < $numRes; $j++) {
                $hour   = rand(7, 21);
                $minute = [0, 15, 30, 45][rand(0, 3)];
                $person = $mexicanNames[array_rand($mexicanNames)];
                $status = $i === 0 ? 'confirmed' : (rand(0, 1) ? 'confirmed' : 'pending');

                Reservation::create([
                    'name'             => $person['name'],
                    'email'            => $person['email'],
                    'phone'            => $person['phone'],
                    'zone'             => $zones[array_rand($zones)],
                    'reservation_date' => $date->copy()->setHour($hour)->setMinute($minute),
                    'guests'           => rand(1, 5),
                    'status'           => $status,
                ]);
            }
        }
    }

    private function getNames(): array
    {
        return [
            ['name' => 'Carlos Ramírez Torres',    'email' => 'carlos.ramirez@gmail.com',  'phone' => '+52 55 3421 8890'],
            ['name' => 'María González López',     'email' => 'mgonzalez@outlook.com',      'phone' => '+52 55 1234 5678'],
            ['name' => 'José Hernández Méndez',    'email' => 'jose.hdz@gmail.com',         'phone' => '+52 55 9876 5432'],
            ['name' => 'Ana Martínez Pérez',       'email' => 'ana.martinez@hotmail.com',   'phone' => '+52 55 2345 6789'],
            ['name' => 'Luis García Flores',       'email' => 'lgarcia@gmail.com',          'phone' => '+52 55 8765 4321'],
            ['name' => 'Sofía López Jiménez',      'email' => 'sofia.lopez@gmail.com',      'phone' => '+52 55 3456 7890'],
            ['name' => 'Miguel Ángel Cruz Rojas',  'email' => 'macruz@hotmail.com',         'phone' => '+52 55 7654 3210'],
            ['name' => 'Valeria Castro Ruiz',      'email' => 'valeria.castro@gmail.com',   'phone' => '+52 55 4567 8901'],
            ['name' => 'Fernando Morales Silva',   'email' => 'fmorales@gmail.com',         'phone' => '+52 55 6543 2109'],
            ['name' => 'Isabella Reyes Guzmán',    'email' => 'isabella.reyes@gmail.com',   'phone' => '+52 55 5678 9012'],
            ['name' => 'Diego Jiménez Vargas',     'email' => 'diego.jv@outlook.com',       'phone' => '+52 55 5432 1098'],
            ['name' => 'Camila Torres Mendoza',    'email' => 'camila.torres@gmail.com',    'phone' => '+52 55 6789 0123'],
            ['name' => 'Alejandro Vega Ortega',    'email' => 'avega@gmail.com',            'phone' => '+52 55 4321 0987'],
            ['name' => 'Fernanda Ramos Castillo',  'email' => 'framos@hotmail.com',         'phone' => '+52 55 7890 1234'],
            ['name' => 'Ricardo Moreno Espinoza',  'email' => 'rmoreno@gmail.com',          'phone' => '+52 55 3210 9876'],
            ['name' => 'Daniela Fuentes Ávila',    'email' => 'daniela.fuentes@gmail.com',  'phone' => '+52 55 8901 2345'],
            ['name' => 'Roberto Sánchez Luna',     'email' => 'rsanchez@gmail.com',         'phone' => '+52 55 2109 8765'],
            ['name' => 'Paola Díaz Aguilar',       'email' => 'pdiaz@outlook.com',          'phone' => '+52 55 9012 3456'],
            ['name' => 'Eduardo Núñez Peña',       'email' => 'enuñez@gmail.com',           'phone' => '+52 55 1098 7654'],
            ['name' => 'Lucía Herrera Campos',     'email' => 'lucia.herrera@gmail.com',    'phone' => '+52 55 0123 4567'],
            ['name' => 'Andrés Medina Romero',     'email' => 'amedina@hotmail.com',        'phone' => '+52 55 9874 3210'],
            ['name' => 'Renata Silva Contreras',   'email' => 'renata.silva@gmail.com',     'phone' => '+52 55 1357 2468'],
            ['name' => 'Sebastián Quiroz Leal',    'email' => 'squiroz@gmail.com',          'phone' => '+52 55 2468 1357'],
            ['name' => 'Mariana Estrada Ibáñez',   'email' => 'mestrada@gmail.com',         'phone' => '+52 55 8642 0975'],
            ['name' => 'Pablo Cervantes Montoya',  'email' => 'pcervantes@outlook.com',     'phone' => '+52 55 9753 1864'],
            ['name' => 'Ximena Gutiérrez Arreola', 'email' => 'xgutierrez@gmail.com',       'phone' => '+52 55 3698 5274'],
            ['name' => 'Emilio Delgado Pacheco',   'email' => 'edelgado@gmail.com',         'phone' => '+52 55 4782 6593'],
            ['name' => 'Natalia Pedraza Ríos',     'email' => 'npedraza@hotmail.com',       'phone' => '+52 55 6317 4852'],
            ['name' => 'Juan Pablo Lara Acosta',   'email' => 'jplara@gmail.com',           'phone' => '+52 55 7524 9163'],
            ['name' => 'Gabriela Trujillo Vázquez','email' => 'gtrujillo@gmail.com',        'phone' => '+52 55 8415 3720'],
        ];
    }
}

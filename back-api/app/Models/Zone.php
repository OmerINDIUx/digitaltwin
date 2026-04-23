<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name', 'icon', 'image', 'description', 'capacity', 'opening_hour', 'closing_hour', 'status',
        'max_reservation_hours', 'rest_days', 'rest_start_hour', 'rest_end_hour', 'schedules'
    ];

    protected $casts = [
        'schedules' => 'array',
    ];
}

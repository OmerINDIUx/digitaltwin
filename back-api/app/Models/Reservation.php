<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'zone',
        'reservation_date',
        'guests',
        'status',
    ];

    protected $casts = [
        'reservation_date' => 'datetime',
    ];
}

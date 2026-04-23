<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name', 'description', 'zone', 'event_date', 
        'duration', 'capacity', 'current_attendees', 'type', 'image',
        'price', 'is_active'
    ];

    protected $casts = [
        'event_date' => 'datetime',
    ];

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }
}

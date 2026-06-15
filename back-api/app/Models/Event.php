<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    public const ACTIVE_REGISTRATION_STATUSES = ['aceptado', 'por_pagar', 'pagado'];

    protected $fillable = [
        'name', 'description', 'zone', 'event_date', 
        'duration', 'capacity', 'current_attendees', 'type', 'image',
        'attachments', 'price', 'is_active'
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'attachments' => 'array',
    ];

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function activeRegistrations()
    {
        return $this->hasMany(EventRegistration::class)
            ->whereIn('status', self::ACTIVE_REGISTRATION_STATUSES);
    }

    public function syncActiveAttendeeCount(): void
    {
        $this->forceFill([
            'current_attendees' => $this->activeRegistrations()->count(),
        ])->save();
    }
}

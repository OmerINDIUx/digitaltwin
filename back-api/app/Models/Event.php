<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    public function getImageAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        $path = preg_replace('#^/?storage/#', '', (string) $value);

        return url('storage/' . ltrim((string) $path, '/'));
    }

    public function getAttachmentsAttribute($value): array
    {
        $attachments = is_array($value) ? $value : json_decode((string) $value, true);

        return collect($attachments ?: [])
            ->map(function ($attachment) {
                if (!is_array($attachment)) {
                    return $attachment;
                }

                $path = $attachment['path'] ?? preg_replace('#^/?storage/#', '', (string) ($attachment['url'] ?? ''));
                $attachment['path'] = ltrim((string) $path, '/');

                if (!empty($attachment['url']) && Str::startsWith($attachment['url'], ['http://', 'https://'])) {
                    return $attachment;
                }

                $attachment['url'] = url('storage/' . $attachment['path']);

                return $attachment;
            })
            ->values()
            ->all();
    }

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

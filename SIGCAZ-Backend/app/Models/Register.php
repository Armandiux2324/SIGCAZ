<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Register extends Model
{
    use HasFactory;

    protected $table = 'registers';

    protected $fillable = [
        'origin_type',
        'state',
        'municipality',
        'group',
        'is_first_time',
        'participation_count',
        'attendance_type',
        'participant_count',
        'accommodation_type',
        'lodging',
        'stay_days',
        'transport_method',
        'folio_delivery_method',
    ];

    protected $casts = [
        'is_first_time' => 'boolean',
        'participation_count' => 'integer',
        'participant_count' => 'integer',
        'stay_days' => 'integer',
    ];

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function getOriginTypeLabelAttribute(): string
    {
        return $this->origin_type === 'national' ? 'Nacional' : 'Estatal';
    }

    public function getAttendanceTypeLabelAttribute(): string
    {
        return $this->attendance_type === 'accompanied' ? 'Acompañado' : 'Solo';
    }

    public function getIsFirstTimeLabelAttribute(): string
    {
        return $this->is_first_time ? 'Sí' : 'No';
    }

    public function getAccommodationTypeLabelAttribute(): string
    {
        return match ($this->accommodation_type) {
            'airbnb' => 'Airbnb',
            'hotel' => 'Hotel',
            'own_home' => 'Casa propia',
            'family_or_friends' => 'Casa de familiares o amigos',
            default => $this->accommodation_type,
        };
    }

    public function getTransportMethodLabelAttribute(): string
    {
        return match ($this->transport_method) {
            'car' => 'Automóvil',
            'airplane' => 'Avión',
            'bus' => 'Autobús',
            default => $this->transport_method,
        };
    }
}

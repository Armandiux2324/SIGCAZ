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
}

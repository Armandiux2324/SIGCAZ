<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Participant extends Model
{
    use HasFactory;

    protected $table = 'participants';

    protected $fillable = [
        'register_id',
        'folio',
        'qr_path',
        'attended_at',
        'first_name',
        'last_name',
        'phone',
        'email',
        'gender',
        'shirt_size',
        'is_first_time',
        'participation_count',
    ];

    protected $casts = [
        'is_first_time' => 'boolean',
        'participation_count' => 'integer',
    ];

    public function register()
    {
        return $this->belongsTo(Register::class);
    }

    public function qrScans()
    {
        return $this->hasMany(QrScan::class);
    }

    public function getGenderLabelAttribute(): string
    {
        return $this->gender === 'male' ? 'Masculino' : 'Femenino';
    }

    public function getIsFirstTimeLabelAttribute(): string
    {
        return $this->is_first_time ? 'Sí' : 'No';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Settings extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = [
        'event_address',
        'event_date',
        'event_image_path',
    ];

    protected $casts = [
        'event_date' => 'datetime',
    ];
}

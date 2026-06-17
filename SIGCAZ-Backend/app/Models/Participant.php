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
        'shirt_size'
    ];

    public function register()
    {
        return $this->belongsTo(Register::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    use HasFactory;

    protected $fillable = ['token', 'start_time', 'expiry', 'duration', 'used'];

    // Cast 'expiry' to a Carbon date instance
    protected $casts = [
        'expiry' => 'datetime',
        'start_time' => 'datetime'
    ];
}


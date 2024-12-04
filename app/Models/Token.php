<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    use HasFactory;

    protected $fillable = ['token', 'start_time', 'expiry', 'duration', 'used', 'guest_name', 'room_no', 'phone', 'voucher', 'car_type'];

    // Cast 'expiry' to a Carbon date instance
    protected $casts = [
        'expiry' => 'datetime',
        'start_time' => 'datetime'
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher', 'id');
    }
}


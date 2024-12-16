<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'charging_port',
        'start_time',
        'end_time',
        'guest_name',
        'room_no',
        'phone',
        'car_type',
        'voucher_name',
        'voucher_duration',
        'voucher_price',
        'used_time',
        'port_history'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'voucher_price' => 'decimal:2',
        'port_history' => 'array'
    ];
}
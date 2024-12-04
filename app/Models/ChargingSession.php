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
        'voucher'
    ];

    // Cast 'start_time' and 'end_time' to Carbon instances
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    // Relationships (if any)
    public function token()
    {
        return $this->belongsTo(Token::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher', 'id');
    }
}

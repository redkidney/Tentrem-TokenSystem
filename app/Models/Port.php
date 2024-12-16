<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Port extends Model
{
    use HasFactory;

    protected $fillable = ['status', 'current_token', 'remaining_time', 'pause_expiry', 'start_time', 'end_time'];

    // Cast 'start_time' and 'end_time' to Carbon instances
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

     public function isPauseExpired()
    {
        return $this->status === 'paused' && 
            $this->pause_expiry && 
            $this->pause_expiry->isPast();
    }
}

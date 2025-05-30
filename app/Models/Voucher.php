<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_name',
        'duration',
        'price',
    ];

    public function chargingSessions()
    {
        return $this->hasMany(ChargingSession::class, 'voucher', 'id');
    }
}

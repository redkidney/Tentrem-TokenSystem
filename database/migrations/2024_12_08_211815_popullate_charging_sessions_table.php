<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::table('charging_sessions')
            ->join('vouchers', 'charging_sessions.voucher', '=', 'vouchers.id')
            ->whereNotNull('charging_sessions.voucher')
            ->orderBy('charging_sessions.id')
            ->chunk(100, function($sessions) {
                foreach ($sessions as $session) {
                    DB::table('charging_sessions')
                        ->where('id', $session->id)
                        ->update([
                            'voucher_name' => $session->voucher_name,
                            'voucher_duration' => $session->duration,
                            'voucher_price' => $session->price
                        ]);
                }
            });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

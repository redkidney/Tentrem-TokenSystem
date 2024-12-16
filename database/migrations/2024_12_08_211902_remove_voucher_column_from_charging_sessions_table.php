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
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropForeign(['voucher']);
            $table->dropColumn('voucher');
        });
    }

    public function down()
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->foreignId('voucher')->nullable();
            $table->foreign('voucher')->references('id')->on('vouchers');
        });
    }
};

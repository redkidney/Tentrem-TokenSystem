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
            $table->string('voucher_name')->nullable()->after('car_type');
            $table->integer('voucher_duration')->nullable()->after('voucher_name');
            $table->decimal('voucher_price', 8, 2)->nullable()->after('voucher_duration');
        });
    }

    public function down()
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropColumn(['voucher_name', 'voucher_duration', 'voucher_price']);
        });
    }
};

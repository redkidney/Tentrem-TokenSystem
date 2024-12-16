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
            $table->string('car_type')->nullable()->after('phone');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropColumn('car_type');
        });
    }
};

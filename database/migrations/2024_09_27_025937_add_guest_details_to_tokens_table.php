<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->string('guest_name')->nullable(); // Add guest name column
            $table->string('room_no')->nullable(); // Add room number column
            $table->string('phone')->nullable(); // Add phone column
        });
    }
    
    public function down()
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->dropColumn('guest_name');
            $table->dropColumn('room_no');
            $table->dropColumn('phone');
        });
    }
    

};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargingSessionsTable extends Migration
{
    public function up()
    {
        Schema::create('charging_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('token', 10)->nullable();
            $table->integer('charging_port');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('guest_name', 255)->nullable();
            $table->string('room_no', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->timestamps();  // includes created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('charging_sessions');
    }
}


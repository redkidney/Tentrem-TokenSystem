<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTokensTable extends Migration
{
    public function up()
    {
        Schema::create('tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('token', 10);
            $table->boolean('used')->default(false);
            $table->timestamp('expiry')->nullable();
            $table->integer('duration')->nullable();  // duration in minutes or seconds based on your use case
            $table->integer('port')->nullable();
            $table->string('guest_name', 255)->nullable();
            $table->string('room_no', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->timestamps();  // includes created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('tokens');
    }
}


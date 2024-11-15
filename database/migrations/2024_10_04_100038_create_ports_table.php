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
        Schema::create('ports', function (Blueprint $table) {
            $table->id();
            $table->integer('port_number')->unique();
            $table->string('status', 255)->default('idle');
            $table->string('current_token', 10)->nullable();
            $table->integer('remaining_time')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->timestamps();
        });

        // Insert initial ports (port 1 and port 2)
        DB::table('ports')->insert([
            ['port_number' => 1, 'status' => 'idle', 'current_token' => null, 'remaining_time' => null],
            ['port_number' => 2, 'status' => 'idle', 'current_token' => null, 'remaining_time' => null],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('ports');
    }
};

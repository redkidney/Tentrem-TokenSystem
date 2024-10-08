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
            $table->integer('port_number')->unique();  // Port 1 or 2
            $table->string('status')->default('idle'); // 'idle' or 'charging'
            $table->unsignedBigInteger('current_token_id')->nullable(); // FK to tokens table
            $table->integer('remaining_time')->nullable(); // Time left in seconds
            $table->timestamps();

            // Optional: Foreign key constraint linking to the tokens table
            $table->foreign('current_token_id')->references('id')->on('tokens')->onDelete('set null');
        });

        // Insert initial ports (port 1 and port 2)
        DB::table('ports')->insert([
            ['port_number' => 1, 'status' => 'idle', 'current_token_id' => null, 'remaining_time' => null],
            ['port_number' => 2, 'status' => 'idle', 'current_token_id' => null, 'remaining_time' => null],
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

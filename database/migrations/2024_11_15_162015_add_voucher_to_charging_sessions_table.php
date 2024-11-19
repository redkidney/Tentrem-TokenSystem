<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            // Add a foreign key column for 'voucher_id' that references 'vouchers'
            $table->unsignedBigInteger('voucher')->nullable();

            // Define the foreign key constraint
            $table->foreign('voucher')
                  ->references('id')
                  ->on('vouchers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['voucher']);

            // Then drop the column
            $table->dropColumn('voucher');
        });
    }
};

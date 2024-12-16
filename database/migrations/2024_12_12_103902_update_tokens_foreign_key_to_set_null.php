<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            // Drop the old foreign key
            $table->dropForeign(['voucher']);

            // Add the new foreign key with SET NULL
            $table->foreign('voucher')
                  ->references('id')
                  ->on('vouchers')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            // Drop the updated foreign key
            $table->dropForeign(['voucher']);

            // Restore the original foreign key behavior
            $table->foreign('voucher')
                  ->references('id')
                  ->on('vouchers');
        });
    }
};

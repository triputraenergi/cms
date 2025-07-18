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
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_code')
                ->charset('utf8mb4') // Match the character set you found
                ->collation('utf8mb4_0900_ai_ci') // <-- IMPORTANT: Match the exact collation you found
                ->nullable()
                ->after('email');

            $table->foreign('company_code')
                ->references('code')
                ->on('companies')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_code']);
            $table->dropColumn('company_code');
        });
    }
};

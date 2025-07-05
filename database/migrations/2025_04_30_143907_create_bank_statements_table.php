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
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();

            $table->string('reference');      // :20:
            $table->string('account_id');     // :25:
            $table->string('statement_number')->nullable(); // :28C:
            $table->string('opening_balance')->nullable();  // :60F:
            $table->string('closing_balance')->nullable();
            $table->string('currency', 3)->nullable();       // extracted from :60F:
            $table->date('opening_balance_date')->nullable(); // parsed from :60F:


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};

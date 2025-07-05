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
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bank_statement_id')->constrained()->onDelete('cascade');
            $table->date('transaction_date');       // parsed from :61:
            $table->enum('type', ['D', 'C']);       // Debit/Credit
            $table->decimal('amount', 15, 2);       // parsed from :61:
            $table->string('reference')->nullable(); // NONREF//xxx or other info from :61:
            $table->text('description')->nullable(); // :86:

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};

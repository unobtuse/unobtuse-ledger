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
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('loan_interest_rate', 5, 2)->nullable()->after('initial_loan_amount')->comment('Annual interest rate percentage');
            $table->integer('loan_term_months')->nullable()->after('loan_interest_rate')->comment('Loan term in months');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['loan_interest_rate', 'loan_term_months']);
        });
    }
};

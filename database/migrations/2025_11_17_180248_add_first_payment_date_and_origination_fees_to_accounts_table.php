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
            $table->date('loan_first_payment_date')->nullable()->after('loan_term_months')->comment('Date of first loan payment');
            $table->decimal('loan_origination_fees', 10, 2)->nullable()->after('loan_first_payment_date')->comment('Origination fees/closing costs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['loan_first_payment_date', 'loan_origination_fees']);
        });
    }
};

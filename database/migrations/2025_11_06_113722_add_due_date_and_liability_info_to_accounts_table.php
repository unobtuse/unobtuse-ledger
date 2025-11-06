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
            // Payment due date fields
            $table->date('payment_due_date')->nullable()->after('credit_limit');
            $table->enum('payment_due_date_source', ['plaid', 'manual'])->nullable()->after('payment_due_date');
            $table->integer('payment_due_day')->nullable()->after('payment_due_date_source'); // 1-31 for recurring day of month
            $table->decimal('minimum_payment_amount', 12, 2)->nullable()->after('payment_due_day');
            $table->decimal('next_payment_amount', 12, 2)->nullable()->after('minimum_payment_amount');
            
            // Interest rate fields
            $table->decimal('interest_rate', 5, 2)->nullable()->after('next_payment_amount'); // APR percentage (e.g., 18.99)
            $table->enum('interest_rate_type', ['fixed', 'variable'])->nullable()->after('interest_rate');
            $table->enum('interest_rate_source', ['plaid', 'manual'])->nullable()->after('interest_rate_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'payment_due_date',
                'payment_due_date_source',
                'payment_due_day',
                'minimum_payment_amount',
                'next_payment_amount',
                'interest_rate',
                'interest_rate_type',
                'interest_rate_source',
            ]);
        });
    }
};

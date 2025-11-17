<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migrates from Plaid to Teller API:
     * 1. Clear all existing transaction and bill data (starting fresh)
     * 2. Rename plaid_token column to teller_token
     * 3. Clear all existing account data
     */
    public function up(): void
    {
        // Clear all transactions (bogus test data)
        DB::table('transactions')->truncate();

        // Clear all bills (bogus test data)
        DB::table('bills')->truncate();

        // Clear all accounts and rename token column
        Schema::table('accounts', function (Blueprint $table) {
            // Drop the old plaid_token column and replace with teller_token
            if (Schema::hasColumn('accounts', 'plaid_token')) {
                $table->dropColumn('plaid_token');
            }
            
            // Add new teller_token column
            $table->string('teller_token')->nullable()->comment('Teller API access token');
            $table->string('teller_account_id')->nullable()->comment('Teller account ID');
        });

        // Clear all accounts
        DB::table('accounts')->truncate();

        // Clear all pay schedules
        DB::table('pay_schedules')->truncate();

        // Clear all budgets
        DB::table('budgets')->truncate();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'teller_token')) {
                $table->dropColumn('teller_token');
            }
            if (Schema::hasColumn('accounts', 'teller_account_id')) {
                $table->dropColumn('teller_account_id');
            }
            
            // Restore plaid_token if rolling back
            $table->string('plaid_token')->nullable();
        });
    }
};

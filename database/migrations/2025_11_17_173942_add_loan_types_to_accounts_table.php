<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL doesn't allow adding enum values with ALTER TABLE
        // We need to use raw SQL to modify the type constraint
        DB::statement("ALTER TABLE accounts DROP CONSTRAINT IF EXISTS accounts_account_type_check");
        DB::statement("ALTER TABLE accounts ADD CONSTRAINT accounts_account_type_check CHECK (account_type IN ('checking', 'savings', 'credit_card', 'investment', 'loan', 'auto_loan', 'mortgage', 'student_loan', 'other'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE accounts DROP CONSTRAINT IF EXISTS accounts_account_type_check");
        DB::statement("ALTER TABLE accounts ADD CONSTRAINT accounts_account_type_check CHECK (account_type IN ('checking', 'savings', 'credit_card', 'investment', 'loan', 'other'))");
    }
};

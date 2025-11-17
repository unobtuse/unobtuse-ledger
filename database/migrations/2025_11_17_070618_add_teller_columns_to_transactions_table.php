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
        Schema::table('transactions', function (Blueprint $table) {
            // Add Teller transaction ID column
            if (!Schema::hasColumn('transactions', 'teller_transaction_id')) {
                $table->string('teller_transaction_id')->nullable()->after('plaid_transaction_id')
                    ->comment('Teller API transaction ID');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'teller_transaction_id')) {
                $table->dropColumn('teller_transaction_id');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Make Plaid fields nullable to support Teller-only accounts
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Make all Plaid fields nullable since we're migrating to Teller
            $table->string('plaid_account_id')->nullable()->change();
            $table->text('plaid_access_token')->nullable()->change();
            $table->string('plaid_item_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Revert to NOT NULL (will fail if there are Teller-only accounts)
            $table->string('plaid_account_id')->nullable(false)->change();
            $table->text('plaid_access_token')->nullable(false)->change();
            $table->string('plaid_item_id')->nullable(false)->change();
        });
    }
};

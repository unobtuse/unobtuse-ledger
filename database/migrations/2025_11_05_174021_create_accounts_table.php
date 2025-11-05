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
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            
            // Plaid identifiers
            $table->string('plaid_account_id')->index();
            $table->text('plaid_access_token'); // Encrypted in model
            $table->string('plaid_item_id')->index();
            
            // Account details
            $table->string('account_name');
            $table->string('official_name')->nullable();
            $table->enum('account_type', ['checking', 'savings', 'credit_card', 'investment', 'loan', 'other']);
            $table->enum('account_subtype', ['checking', 'savings', 'credit card', 'money market', 'cd', 'ira', '401k', 'student', 'mortgage', 'auto', 'other'])->nullable();
            
            // Institution details
            $table->string('institution_id')->nullable();
            $table->string('institution_name');
            
            // Balance information
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('available_balance', 12, 2)->nullable();
            $table->decimal('credit_limit', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            
            // Account numbers (masked)
            $table->string('mask')->nullable(); // Last 4 digits
            $table->string('account_number')->nullable(); // Encrypted if stored
            $table->string('routing_number')->nullable(); // Encrypted if stored
            
            // Sync status
            $table->enum('sync_status', ['syncing', 'synced', 'failed', 'disabled'])->default('synced');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('sync_error')->nullable();
            
            // Metadata
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Additional data from Plaid
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};

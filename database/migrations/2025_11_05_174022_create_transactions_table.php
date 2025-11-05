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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('account_id')->constrained()->onDelete('cascade');
            
            // Plaid identifiers
            $table->string('plaid_transaction_id')->unique()->nullable();
            
            // Transaction details
            $table->string('name'); // Merchant/description
            $table->string('merchant_name')->nullable();
            $table->decimal('amount', 12, 2); // Positive for debits, negative for credits
            $table->string('iso_currency_code', 3)->default('USD');
            $table->date('transaction_date');
            $table->date('authorized_date')->nullable();
            $table->date('posted_date')->nullable();
            
            // Categorization
            $table->string('category')->nullable(); // Our simplified category
            $table->json('plaid_categories')->nullable(); // Plaid's hierarchical categories
            $table->string('category_id')->nullable(); // Plaid category ID
            $table->integer('category_confidence')->nullable(); // 0-100
            
            // Transaction type
            $table->enum('transaction_type', ['debit', 'credit', 'transfer'])->default('debit');
            $table->boolean('pending')->default(false);
            
            // Recurring transaction detection
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', ['daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'annual'])->nullable();
            $table->uuid('recurring_group_id')->nullable(); // Group similar recurring transactions
            
            // Location (if available)
            $table->string('location_address')->nullable();
            $table->string('location_city')->nullable();
            $table->string('location_region')->nullable();
            $table->string('location_postal_code')->nullable();
            $table->string('location_country', 2)->nullable();
            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_lon', 10, 7)->nullable();
            
            // Payment channel
            $table->enum('payment_channel', ['online', 'in_store', 'other'])->nullable();
            
            // User modifications
            $table->string('user_category')->nullable(); // User can override category
            $table->text('user_notes')->nullable();
            $table->json('tags')->nullable(); // User-defined tags
            
            // Metadata
            $table->json('metadata')->nullable(); // Additional Plaid data
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'transaction_date']);
            $table->index(['account_id', 'transaction_date']);
            $table->index(['merchant_name', 'amount']);
            $table->index(['is_recurring', 'recurring_frequency']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

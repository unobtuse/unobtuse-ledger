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
        Schema::create('budgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            
            // Budget period
            $table->string('month', 7); // Format: YYYY-MM
            
            // Income
            $table->decimal('total_income', 12, 2)->default(0);
            $table->decimal('expected_income', 12, 2)->nullable();
            
            // Expenses
            $table->decimal('bills_total', 12, 2)->default(0);
            $table->decimal('bills_paid', 12, 2)->default(0);
            $table->decimal('bills_pending', 12, 2)->default(0);
            
            // Spending
            $table->decimal('spending_total', 12, 2)->default(0);
            $table->decimal('spending_by_category', 12, 2)->nullable();
            $table->json('category_breakdown')->nullable(); // {category: amount}
            
            // Rent allocation (25% rule)
            $table->decimal('rent_allocation', 12, 2)->nullable();
            $table->boolean('rent_paid')->default(false);
            
            // Calculated budgets
            $table->decimal('remaining_budget', 12, 2)->default(0);
            $table->decimal('available_to_spend', 12, 2)->default(0);
            
            // Goals and targets
            $table->decimal('savings_goal', 12, 2)->nullable();
            $table->decimal('savings_actual', 12, 2)->default(0);
            $table->decimal('spending_limit', 12, 2)->nullable();
            
            // Status
            $table->enum('status', ['draft', 'active', 'completed', 'overspent'])->default('active');
            
            // Statistics
            $table->integer('transactions_count')->default(0);
            $table->integer('bills_count')->default(0);
            
            // Metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['user_id', 'month']);
            $table->index(['user_id', 'status']);
            $table->index('month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};

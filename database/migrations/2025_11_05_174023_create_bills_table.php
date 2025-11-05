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
        Schema::create('bills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('account_id')->nullable()->constrained()->onDelete('set null');
            
            // Bill details
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            
            // Due date information
            $table->date('due_date');
            $table->date('next_due_date');
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly', 'quarterly', 'annual', 'custom'])->default('monthly');
            $table->integer('frequency_value')->nullable(); // For custom frequencies
            
            // Bill categorization
            $table->enum('category', [
                'rent',
                'mortgage',
                'utilities',
                'internet',
                'phone',
                'insurance',
                'subscription',
                'loan',
                'credit_card',
                'other'
            ])->default('other');
            
            // Payment information
            $table->enum('payment_status', ['upcoming', 'due', 'overdue', 'paid', 'scheduled'])->default('upcoming');
            $table->date('last_payment_date')->nullable();
            $table->decimal('last_payment_amount', 12, 2)->nullable();
            
            // Auto-pay settings
            $table->boolean('is_autopay')->default(false);
            $table->string('autopay_account')->nullable();
            
            // Payment link
            $table->string('payment_link')->nullable();
            $table->string('payee_name')->nullable();
            
            // Detection information
            $table->boolean('auto_detected')->default(false);
            $table->uuid('source_transaction_id')->nullable(); // Transaction that triggered detection
            $table->integer('detection_confidence')->nullable(); // 0-100
            
            // Reminder settings
            $table->boolean('reminder_enabled')->default(true);
            $table->integer('reminder_days_before')->default(3);
            
            // User notes
            $table->text('notes')->nullable();
            
            // Priority
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'next_due_date']);
            $table->index(['user_id', 'payment_status']);
            $table->index(['category', 'frequency']);
            $table->index('auto_detected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};

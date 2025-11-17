<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates payments table to track Teller payment transactions
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('account_id')->constrained()->onDelete('cascade');
            
            // Teller payment ID
            $table->string('teller_payment_id')->nullable()->unique();
            
            // Payment details
            $table->string('recipient_name'); // Payee name
            $table->string('recipient_account_number')->nullable(); // Masked account number
            $table->string('recipient_routing_number')->nullable(); // Routing number for ACH
            $table->enum('payment_type', ['ach', 'wire', 'check'])->default('ach');
            $table->enum('payment_method', ['one_time', 'recurring'])->default('one_time');
            
            // Amount and currency
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            
            // Payment status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->text('status_message')->nullable(); // Error message if failed
            
            // Scheduling
            $table->timestamp('scheduled_date')->nullable(); // When payment should be processed
            $table->timestamp('processed_date')->nullable(); // When payment was actually processed
            
            // Recurring payment details
            $table->string('recurrence_frequency')->nullable(); // daily, weekly, monthly, etc.
            $table->date('recurrence_end_date')->nullable(); // When recurring payments should stop
            
            // Metadata
            $table->text('memo')->nullable(); // Payment memo/note
            $table->json('metadata')->nullable(); // Additional Teller payment data
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['account_id', 'status']);
            $table->index('scheduled_date');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

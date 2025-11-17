<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates webhook_events table to log all Teller webhook events
     */
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_type'); // account.disconnected, transaction.created, etc.
            $table->string('teller_event_id')->nullable()->unique(); // Teller's event ID if provided
            $table->json('payload'); // Full webhook payload
            $table->string('account_id')->nullable(); // Related account ID if applicable
            $table->string('transaction_id')->nullable(); // Related transaction ID if applicable
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('event_type');
            $table->index('status');
            $table->index('account_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};

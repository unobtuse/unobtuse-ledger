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
        Schema::create('pay_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            
            // Pay schedule configuration
            $table->enum('frequency', ['weekly', 'biweekly', 'semimonthly', 'monthly', 'custom'])->default('biweekly');
            
            // For weekly/biweekly
            $table->enum('pay_day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->nullable();
            
            // For monthly/semimonthly
            $table->integer('pay_day_of_month_1')->nullable(); // 1-31
            $table->integer('pay_day_of_month_2')->nullable(); // For semimonthly (1-31)
            
            // Custom schedule (JSON array of dates)
            $table->json('custom_schedule')->nullable();
            
            // Next pay date
            $table->date('next_pay_date');
            
            // Pay amount information
            $table->decimal('gross_pay', 12, 2)->nullable();
            $table->decimal('net_pay', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            
            // Employer information
            $table->string('employer_name')->nullable();
            
            // Active status
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index('next_pay_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_schedules');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates institutions table to cache Teller institution data
     */
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->string('id')->primary(); // Teller institution ID (e.g., 'capital_one')
            $table->string('name'); // Institution name (e.g., 'Capital One')
            $table->string('logo_url')->nullable(); // Institution logo URL from Teller
            $table->json('capabilities')->nullable(); // Supported features (payments, etc.)
            $table->json('metadata')->nullable(); // Additional institution data
            $table->timestamp('last_fetched_at')->nullable(); // When we last fetched from Teller
            $table->timestamps();
            
            // Indexes
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};

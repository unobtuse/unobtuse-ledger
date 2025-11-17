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
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('is_active');
            $table->string('statement_file_path')->nullable()->after('is_manual');
        });
        
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['is_manual', 'statement_file_path']);
        });
        
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('is_manual');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create leave_credits table with all necessary fields for tracking:
     * - VL (Vacation Leave) credits, usage, pending, and carryover
     * - SL (Sick Leave) credits, usage, and pending
     * - Birthday leave tracking
     */
    public function up(): void
    {
        Schema::create('leave_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->year('year');
            
            // Vacation Leave tracking
            $table->decimal('vl_credits', 8, 2)->default(0);
            $table->decimal('vl_used', 8, 2)->default(0);
            $table->decimal('vl_pending', 8, 2)->default(0);
            $table->decimal('vl_carried_over', 8, 2)->default(0);
            $table->decimal('vl_carried_over_used', 5, 2)->default(0);
            
            // Sick Leave tracking
            $table->decimal('sl_credits', 8, 2)->default(0);
            $table->decimal('sl_used', 8, 2)->default(0);
            $table->decimal('sl_pending', 8, 2)->default(0);
            
            // Birthday Leave tracking
            $table->decimal('birthday_leave_count', 3, 2)->default(1.00);
            
            $table->timestamps();
            
            // Unique constraint: one record per user per year
            $table->unique(['user_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_credits');
    }
};

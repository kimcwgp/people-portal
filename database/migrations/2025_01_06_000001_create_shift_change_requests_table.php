<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('current_shift_id')->nullable()->constrained('shifts')->onDelete('set null');
            $table->foreignId('requested_shift_id')->constrained('shifts')->onDelete('cascade');
            $table->date('effective_date');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'applied'])->default('pending');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('approver_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['approver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_change_requests');
    }
};

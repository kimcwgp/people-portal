<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('leaves_type_id')->constrained('leaves_type')->onDelete('restrict');
            
            // Leave details
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('duration', [
                'All Day', 
                'Half Day (8am to 12nn)', 
                'Half Day (1pm to 5pm)', 
                'Custom'
            ])->default('All Day');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->string('attachment')->nullable();
            
            // Approval details
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_note')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('leaves');
    }
};
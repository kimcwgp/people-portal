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
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('project_manager_id')->nullable();
            $table->unsignedBigInteger('user_id'); 
            $table->unsignedBigInteger('approver_id');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->date('ot_date');
            $table->text('notes')->nullable();
            $table->time('time_in');
            $table->time('time_out');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('project_manager_id')->references('id')->on('users')->onDelete('set null'); 
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade'); 
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->index('status');
            $table->index('ot_date');
            $table->index('project_manager_id');
            $table->index('user_id'); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('overtimes');
    }
};
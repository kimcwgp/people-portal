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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); 
            $table->text('notes')->nullable();
            $table->json('healthcheckJson')->nullable();
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->date('attendance_date');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('shift_id'); 
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');

            $table->index('user_id');
            $table->index('attendance_date');
            $table->index('shift_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the table if it exists
        Schema::dropIfExists('attendances');
    }
};
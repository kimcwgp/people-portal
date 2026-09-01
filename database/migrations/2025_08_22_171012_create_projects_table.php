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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('project_name')->nullable();
            $table->unsignedBigInteger('project_type_id');
            $table->string('clickup_url')->nullable();
            $table->unsignedBigInteger('pm_id');
            $table->string('glip_url')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key references
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('pm_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('project_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projects');
    }
};

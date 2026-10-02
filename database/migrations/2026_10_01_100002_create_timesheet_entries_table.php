<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row of the weekly grid: a project/task line with seven day columns.
     *
     * Hours are stored as seven columns rather than child rows because the grid
     * edits a whole line at once and the row total is then a plain sum -- no
     * join needed to render the table.
     */
    public function up(): void
    {
        Schema::create('timesheet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timesheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('project_ticket', 100)->nullable();
            $table->foreignId('time_type_id')->nullable()->constrained('time_types')->nullOnDelete();
            $table->text('memo')->nullable();

            foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $day) {
                $table->decimal("{$day}_hours", 5, 2)->default(0);
            }

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['timesheet_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheet_entries');
    }
};

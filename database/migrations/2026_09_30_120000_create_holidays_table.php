<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Holidays shown on the dashboard, split into the two calendars the
     * business observes: the client-facing "Partners" calendar and the
     * internal "People" calendar.
     */
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date');
            $table->enum('calendar', ['partners', 'people'])->default('people');
            $table->enum('type', ['regular', 'special_non_working', 'company'])->default('regular');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // The dashboard always queries "this month, this calendar".
            $table->index(['date', 'calendar']);
            $table->index('is_active');
            $table->unique(['date', 'name', 'calendar'], 'holidays_date_name_calendar_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};

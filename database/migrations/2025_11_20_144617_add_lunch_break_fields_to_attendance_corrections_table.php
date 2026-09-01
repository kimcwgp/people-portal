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
        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->time('corrected_lunch_start')->nullable()->after('corrected_time_out');
            $table->time('corrected_lunch_end')->nullable()->after('corrected_lunch_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->dropColumn(['corrected_lunch_start', 'corrected_lunch_end']);
        });
    }
};

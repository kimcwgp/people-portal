<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Comprehensive update to add all necessary leave credit tracking fields:
     * - Add vl_carried_over_used (track carryover usage separately)
     * - Add birthday_leave_count (track birthday leave availability)
     * - Remove sl_carried_over (sick leave doesn't carry over)
     */
    public function up(): void
    {
        // The create_leave_credits_table migration was later amended to include
        // these same columns, so on a fresh database they already exist. Each
        // change is guarded so this runs on both fresh and existing databases.
        Schema::table('leave_credits', function (Blueprint $table) {
            // Add VL carryover used tracking
            if (! Schema::hasColumn('leave_credits', 'vl_carried_over_used')) {
                $table->decimal('vl_carried_over_used', 5, 2)->default(0)->after('vl_carried_over');
            }

            // Add birthday leave count (default 1 day per year)
            if (! Schema::hasColumn('leave_credits', 'birthday_leave_count')) {
                $table->decimal('birthday_leave_count', 3, 2)->default(1.00)->after('sl_pending');
            }

            // Remove sl_carried_over (sick leave doesn't carry over)
            if (Schema::hasColumn('leave_credits', 'sl_carried_over')) {
                $table->dropColumn('sl_carried_over');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_credits', function (Blueprint $table) {
            // Restore sl_carried_over
            $table->decimal('sl_carried_over', 5, 2)->default(0)->after('vl_carried_over');
            
            // Remove added columns
            $table->dropColumn(['vl_carried_over_used', 'birthday_leave_count']);
        });
    }
};

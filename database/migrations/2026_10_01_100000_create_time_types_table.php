<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lookup for the badge shown in the Time Type column of the weekly grid.
     * 'tint' holds a design-system status key (green/orange/purple/...), so the
     * badge colours stay inside the palette rather than being free-form hex.
     */
    public function up(): void
    {
        Schema::create('time_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('tint', 20)->default('gray');
            $table->text('description')->nullable();
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_types');
    }
};

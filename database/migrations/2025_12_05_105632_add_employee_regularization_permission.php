<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $guards = ['web', 'sanctum'];
        
        foreach ($guards as $guard) {
            if (!Permission::where('name', 'edit employee regularization')->where('guard_name', $guard)->exists()) {
                Permission::create([
                    'name' => 'edit employee regularization',
                    'guard_name' => $guard
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'edit employee regularization')->delete();
    }
};

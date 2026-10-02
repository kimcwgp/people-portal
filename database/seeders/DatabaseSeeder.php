<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        $this->call([
            TeamSeeder::class,
            RolePermissionSeeder::class,
            TestUserSeeder::class,
            LeaveTypeSeeder::class,
            PositionSeeder::class,
            UserPositionSeeder::class,
            ClientSeeder::class,
            UserClientSeeder::class,
        ]);
    }
}

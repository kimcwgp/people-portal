<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::orderBy('id')->get();

        if ($clients->count() < 5) {
            $this->command->warn('At least 5 clients are required.');
            return;
        }

        $users = User::orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found.');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Client assignments
        |--------------------------------------------------------------------------
        |
        | Client IDs are based on the 5 test clients:
        |
        | 1 = Acme Corporation
        | 2 = BrightPath Solutions
        | 3 = Northstar Retail
        | 4 = Greenfield Industries
        | 5 = Summit Media Group
        |
        */

        foreach ($users as $index => $user) {
            switch ($index % 5) {
                // User 1, 6, 11, ...
                case 0:
                    $clientIds = [$clients[0]->id];
                    break;

                // User 2, 7, 12, ...
                case 1:
                    $clientIds = [
                        $clients[0]->id,
                        $clients[1]->id,
                    ];
                    break;

                // User 3, 8, 13, ...
                case 2:
                    $clientIds = [
                        $clients[1]->id,
                        $clients[2]->id,
                        $clients[3]->id,
                    ];
                    break;

                // User 4, 9, 14, ...
                case 3:
                    $clientIds = [
                        $clients[2]->id,
                        $clients[4]->id,
                    ];
                    break;

                // User 5, 10, 15, ...
                case 4:
                    $clientIds = [
                        $clients[3]->id,
                        $clients[4]->id,
                    ];
                    break;
            }

            $user->clients()->sync($clientIds);
        }

        $this->command->info(
            "Assigned {$users->count()} users to the test clients."
        );
    }
}
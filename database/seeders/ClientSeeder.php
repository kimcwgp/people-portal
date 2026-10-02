<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'name' => 'Acme Corporation',
                'parent_company' => 'Acme Holdings Inc.',
                'contact_name' => 'John Smith',
                'contact_number' => '+1 555-0101',
                'description' => 'A global technology and business solutions company.',
            ],
            [
                'name' => 'BrightPath Solutions',
                'parent_company' => 'BrightPath Group',
                'contact_name' => 'Sarah Johnson',
                'contact_number' => '+1 555-0102',
                'description' => 'Provides consulting and digital transformation services.',
            ],
            [
                'name' => 'Northstar Retail',
                'parent_company' => 'Northstar International',
                'contact_name' => 'Michael Davis',
                'contact_number' => '+1 555-0103',
                'description' => 'A retail company specializing in consumer products and e-commerce.',
            ],
            [
                'name' => 'Greenfield Industries',
                'parent_company' => 'Greenfield Holdings',
                'contact_name' => 'Emily Wilson',
                'contact_number' => '+1 555-0104',
                'description' => 'An industrial company providing manufacturing and supply chain solutions.',
            ],
            [
                'name' => 'Summit Media Group',
                'parent_company' => 'Summit Global',
                'contact_name' => 'Daniel Brown',
                'contact_number' => '+1 555-0105',
                'description' => 'A media and marketing company focused on digital content and advertising.',
            ],
        ];

        foreach ($clients as $client) {
            Client::create($client);
        }
    }
}
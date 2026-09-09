<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    private const CLIENTS = [
        ['name' => 'Northwind Logistics',      'parent_company' => 'Northwind Group',        'contact_name' => 'Sarah Whitfield', 'contact_number' => '+1-415-555-0134', 'description' => 'Freight and warehousing operator. Owns the fleet tracking portal and the driver mobile app.'],
        ['name' => 'Bayanihan Microfinance',   'parent_company' => null,                     'contact_name' => 'Ramon Estrella',  'contact_number' => '+63-2-8555-0177', 'description' => 'Rural lending cooperative. Loan origination system and branch reporting.'],
        ['name' => 'Harborline Insurance',     'parent_company' => 'Harborline Holdings',    'contact_name' => 'Grace Tan',       'contact_number' => '+65-6555-0192',   'description' => 'General insurance provider. Policy quoting engine and claims intake.'],
        ['name' => 'Verdant Agritech',         'parent_company' => null,                     'contact_name' => 'Miguel Alvarez',  'contact_number' => '+61-2-5550-0148', 'description' => 'Precision farming sensors. Dashboards for yield and irrigation telemetry.'],
        ['name' => 'Cascade Health Partners',  'parent_company' => 'Cascade Health Systems', 'contact_name' => 'Dr. Emily Sato',  'contact_number' => '+1-206-555-0110', 'description' => 'Clinic network. Patient scheduling and HIPAA-scoped records portal.'],
        ['name' => 'Lumina Retail Group',      'parent_company' => 'Lumina Holdings',        'contact_name' => 'Patrick Uy',      'contact_number' => '+63-2-8555-0121', 'description' => 'Department store chain. E-commerce storefront and loyalty program.'],
        ['name' => 'Ironbark Construction',    'parent_company' => null,                     'contact_name' => 'Dean Callahan',   'contact_number' => '+61-3-5550-0163', 'description' => 'Commercial builder. Site inspection app and subcontractor billing.'],
        ['name' => 'Solstice Media',           'parent_company' => 'Solstice Network',       'contact_name' => 'Aiko Tanaka',     'contact_number' => '+81-3-5550-0155', 'description' => 'Digital publisher. Content CMS, ad ops tooling and audience analytics.'],
        ['name' => 'Pioneer Energy Solutions', 'parent_company' => 'Pioneer Industrial',     'contact_name' => 'Hassan Karim',    'contact_number' => '+971-4-555-0186', 'description' => 'Solar installer. Quotation calculator and maintenance ticketing.'],
        ['name' => 'Meridian Education Trust', 'parent_company' => null,                     'contact_name' => 'Claire Bennett',  'contact_number' => '+44-20-7555-0139', 'description' => 'Private school group. Student information system and parent portal.'],
    ];

    public function run(): void
    {
        foreach (self::CLIENTS as $data) {
            $client = Client::firstOrCreate(
                ['name' => $data['name']],
                [
                    'parent_company' => $data['parent_company'],
                    'contact_name' => $data['contact_name'],
                    'contact_number' => $data['contact_number'],
                ]
            );

            // description is not mass-assignable on the model.
            $client->description = $data['description'];
            $client->save();
        }

        $this->command->info(count(self::CLIENTS) . ' clients seeded.');
    }
}

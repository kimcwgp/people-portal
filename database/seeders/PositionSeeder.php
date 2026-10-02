<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $positions = [
            [
                'name' => 'UI/UX',
                'description' => 'Design intuitive, user-centered interfaces and experiences that balance usability, accessibility, and visual appeal across digital products.'
            ],
            [
                'name' => 'Technical Consultant',
                'description' => 'Provide technical guidance and solutions by analyzing client needs, recommending appropriate technologies, and supporting the implementation and optimization of digital systems.'
            ],
            [
                'name' => 'Shopify Developer',
                'description' => 'Develop and customize Shopify stores by building responsive, user-friendly e-commerce experiences, integrating features, and optimizing performance and functionality.'
            ],
            [
                'name' => 'AI Automation Specialist',
                'description' => 'Design and implement AI-powered automations that streamline workflows, reduce repetitive tasks, and improve business efficiency through intelligent tools and integrations.'
            ],
            [
                'name' => 'Team Lead',
                'description' => 'Lead and coordinate development teams by managing projects, guiding team members, ensuring quality standards, and delivering solutions efficiently.'
            ]
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }
    }
}

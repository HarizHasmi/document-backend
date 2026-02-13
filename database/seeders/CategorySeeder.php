<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Policy' => 'Official policies and compliance documents.',
            'Report' => 'Operational and performance reporting documents.',
            'Template' => 'Reusable templates for recurring tasks.',
            'Guide' => 'Instructional guides and process walkthroughs.',
            'Form' => 'Forms used for requests and internal workflows.',
            'Other' => 'Other supporting documents not in standard categories.',
        ];

        foreach ($categories as $title => $description) {
            Category::updateOrCreate(
                ['title' => $title],
                ['description' => $description],
            );
        }
    }
}

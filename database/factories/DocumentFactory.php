<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(10),
            'file_name' => 'dummy.pdf',
            'file_path' => 'documents/dummy.pdf',
            'file_type' => 'pdf',
            'file_size' => rand(1000,5000),
            'category_id' => rand(1,6),
            'department_id' => rand(1,5),
            'uploaded_by' => rand(1,12),
            'access_level' => fake()->randomElement(['public','department','private']),
            'download_count' => rand(0,20),
        ];
    }
}

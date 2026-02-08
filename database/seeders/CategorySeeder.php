<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cats = ['Policy','Report','Template','Guide','Form','Other'];
        foreach ($cats as $c) {
            Category::create(['title'=>$c]);
        }
    }
}

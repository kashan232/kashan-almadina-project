<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'Battery' => ['xp-140', 'xp-190', 'xp-220', 'xp-180'],
            
        ];

        foreach ($data as $categoryName => $subcategories) {
            $category = Category::create(['name' => $categoryName]);

            foreach ($subcategories as $sub) {
                Subcategory::create([
                    'category_id' => $category->id,
                    'name' => $sub,
                ]);
            }
        }
    }
}

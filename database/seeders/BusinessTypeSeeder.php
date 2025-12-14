<?php

namespace Database\Seeders;

use App\Models\BusinessCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BusinessCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businessCategories = [
            ['type' => 'Retail'],
            ['type' => 'Food & Beverage'],
            ['type' => 'Health & Wellness'],
            ['type' => 'Professional Services'],
            ['type' => 'Technology'],
            ['type' => 'Arts & Entertainment'],
            ['type' => 'Education & Training'],
            ['type' => 'Travel & Hospitality'],
            ['type' => 'Real Estate'],
            ['type' => 'Automotive'],
            ['type' => 'Manufacturing'],
            ['type' => 'Construction'],
            ['type' => 'Finance & Insurance'],
            ['type' => 'Agriculture'],
            ['type' => 'Other']
        ];

        foreach ($businessCategories as $type) {
            BusinessCategory::firstOrCreate($type);
        }
    }
}
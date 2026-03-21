<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Enums\PlanEnum;
use App\Enums\SocialPlanEnum;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // साफ करो पहले (optional)
        Plan::truncate();
        PlanFeature::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 🔵 Normal Plans
        foreach (PlanEnum::cases() as $case) {
            $data = $case->details();

            $plan = Plan::create([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'original_price' => $data['original_price'] ?? null,
                'discounted_price' => $data['discounted_price'],
                'discount' => $data['discount'] ?? null,
                'duration' => $data['duration'] ?? null,
                'highlight' => $data['highlight'] ?? false,
                'badge' => $data['badge'] ?? null,
                'tagline' => $data['tagline'] ?? null,
                'button_text' => $data['button_text'] ?? null,
                'button_class' => $data['button_class'] ?? null,
                'border_class' => $data['border_class'] ?? null,
                'type' => 'normal',
            ]);

            foreach ($data['features'] as $feature) {
                $plan->features()->create([
                    'text' => $feature['text'],
                    'included' => $feature['included'],
                ]);
            }
        }

        // 🟣 Social Plans
        foreach (SocialPlanEnum::cases() as $case) {
            $data = $case->details();

            $plan = Plan::create([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'original_price' => $data['original_price'] ?? null,
                'discounted_price' => $data['discounted_price'],
                'discount' => $data['discount'] ?? null,
                'duration' => $data['duration'] ?? null,
                'highlight' => $data['highlight'] ?? false,
                'badge' => $data['badge'] ?? null,
                'tagline' => $data['tagline'] ?? null,
                'button_text' => $data['button_text'] ?? null,
                'button_class' => $data['button_class'] ?? null,
                'border_class' => $data['border_class'] ?? null,
                'type' => 'social',
            ]);

            foreach ($data['features'] as $feature) {
                $plan->features()->create([
                    'text' => $feature['text'],
                    'included' => $feature['included'],
                ]);
            }
        }
    }
}

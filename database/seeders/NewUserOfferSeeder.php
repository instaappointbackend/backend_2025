<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Offer;

class NewUserOfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Offer::updateOrCreate(
            ['coupon_code' => 'NEWUSER50'], // unique identifier
            [
                'title' => 'Welcome Offer for New Users',
                'description' => 'Get $50 off on your first order!',
                'discount_type' => 'fixed',
                'discount_fixed' => 50,
                'discount_percentage' => null,
                'start_date' => now(),
                'end_date' => now()->addYear(),
                'is_active' => true,
                'new_user_only' => true,
                'offer_type' => Offer::TYPE_ADMIN,
                'used_count' => 0
            ]
        );
    }
}

<?php

namespace App\Enums;

enum PlanEnum: string
{
    case BASIC = 'basic';
    case STANDARD = 'standard';
    case SUPER_SAVING = 'super_saving';

    /**
     * Get details for a specific plan
     */
    public function details(): array
    {
        return match ($this) {
            self::BASIC => [
                'title' => 'Basic',
                'slug' => 'basic',
                'original_price' => 2796,
                'discounted_price' => 699,
                'discount' => '75% OFF',
                'duration' => '1 Year',
                'highlight' => false,
                'button_text' => 'Get Started',
                'button_class' => 'btn-outline-primary',
                'border_class' => 'border-primary',
                'features' => [
                    ['text' => 'Unlimited Appointments', 'included' => true],
                    ['text' => 'Appointment Reminders', 'included' => true],
                    ['text' => 'Customer Support (chat/email)', 'included' => true],
                    ['text' => 'Upload up to 5 Videos', 'included' => true],
                    ['text' => 'Upload up to 20 Videos', 'included' => false],
                    ['text' => 'Custom Logo Design', 'included' => false],
                    ['text' => 'Personalized Service Categories', 'included' => false],
                    ['text' => 'Priority Support', 'included' => false],
                    ['text' => 'Unlimited Video Uploads', 'included' => false],
                    ['text' => 'Dedicated Support Agent', 'included' => false],
                    ['text' => 'Step-by-Step App Tutorial', 'included' => false],
                    ['text' => 'Add 2 Custom Categories', 'included' => false],
                    ['text' => '1 Free Reel Shoot', 'included' => false],
                ],
            ],

            self::STANDARD => [
                'title' => 'Standard',
                'slug' => 'standard',
                'original_price' => 3996,
                'discounted_price' => 999,
                'discount' => '75% OFF',
                'duration' => '1 Year',
                'highlight' => true,
                'badge' => 'Most Popular',
                'button_text' => 'Choose Plan',
                'button_class' => 'btn-warning text-dark fw-bold',
                'border_class' => 'border-warning',
                'features' => [
                    ['text' => 'Unlimited Appointments', 'included' => true],
                    ['text' => 'Appointment Reminders', 'included' => true],
                    ['text' => 'Customer Support (chat/email)', 'included' => true],
                    ['text' => 'Upload up to 5 Videos', 'included' => true],
                    ['text' => 'Upload up to 20 Videos', 'included' => true],
                    ['text' => 'Custom Logo Design', 'included' => true],
                    ['text' => 'Personalized Service Categories', 'included' => true],
                    ['text' => 'Priority Support', 'included' => true],
                    ['text' => 'Unlimited Video Uploads', 'included' => false],
                    ['text' => 'Dedicated Support Agent', 'included' => false],
                    ['text' => 'Step-by-Step App Tutorial', 'included' => false],
                    ['text' => 'Add 2 Custom Categories', 'included' => false],
                    ['text' => '1 Free Reel Shoot', 'included' => false],
                ],
            ],

            self::SUPER_SAVING => [
                'title' => 'Super Saving',
                'slug' => 'super_saving',
                'original_price' => 6000,
                'discounted_price' => 1500,
                'discount' => '75% OFF',
                'duration' => '1 Year',
                'highlight' => false,
                'button_text' => 'Start Saving',
                'button_class' => 'btn-success',
                'border_class' => 'border-success',
                'features' => [
                    ['text' => 'Unlimited Appointments', 'included' => true],
                    ['text' => 'Appointment Reminders', 'included' => true],
                    ['text' => 'Customer Support (chat/email)', 'included' => true],
                    ['text' => 'Upload up to 5 Videos', 'included' => true],
                    ['text' => 'Upload up to 20 Videos', 'included' => true],
                    ['text' => 'Custom Logo Design', 'included' => true],
                    ['text' => 'Personalized Service Categories', 'included' => true],
                    ['text' => 'Priority Support', 'included' => true],
                    ['text' => 'Unlimited Video Uploads', 'included' => true],
                    ['text' => 'Dedicated Support Agent', 'included' => true],
                    ['text' => 'Step-by-Step App Tutorial', 'included' => true],
                    ['text' => 'Add 2 Custom Categories', 'included' => true],
                    ['text' => '1 Free Reel Shoot', 'included' => true],
                ],
            ],
        };
    }

    /**
     * 🔹 Get all plans (for pricing page)
     */
    public static function getAllPlans(): array
    {
        return array_map(fn($plan) => $plan->details(), self::cases());
    }

    /**
     * 🔹 Get plan by title or slug (for subscription page)
     */
    public static function getPlanByTitle(string $title): ?array
    {
        $slug = strtolower(str_replace(' ', '_', $title));

        $plan = collect(self::cases())
            ->first(fn($case) => $case->value === $slug);

        return $plan?->details();
    }
}

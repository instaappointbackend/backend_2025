<?php

namespace App\Enums;

enum SocialPlanEnum: string
{
    case SILVER = 'silver';
    case PLATINUM = 'platinum';
    case GOLD = 'gold';

    /**
     * Get details for a specific plan
     */
    public function details(): array
    {
        return match ($this) {

            self::SILVER => [
                'title' => 'Silver',
                'slug' => 'silver',
                'original_price' => 4999,
                'discounted_price' => 2999,
                'discount' => 'Limited Offer',
                'duration' => '30 Days',
                'highlight' => false,
                'badge' => null,
                'tagline' => 'Establish a consistent Instagram presence and look active & professional.',
                'button_text' => 'Choose Silver',
                'button_class' => 'btn-outline-secondary',
                'border_class' => 'border-secondary',
                'features' => [
                    ['text' => '2 Reels Shoot', 'included' => true],
                    ['text' => '4 Feed Posts + 4 Stories', 'included' => true],
                    ['text' => 'Basic Content Planning', 'included' => true],
                    ['text' => 'Caption + Hashtag Strategy', 'included' => true],
                    ['text' => 'Instagram Management – 30 Days', 'included' => true],
                    ['text' => 'Expected Organic Reach Improvement', 'included' => true],

                    ['text' => 'Content Calendar', 'included' => false],
                    ['text' => 'CTA Optimization', 'included' => false],
                    ['text' => 'Performance Tracking', 'included' => false],
                    ['text' => 'Brand Positioning', 'included' => false],
                    ['text' => 'Advanced Content Strategy', 'included' => false],
                ],
            ],

            self::PLATINUM => [
                'title' => 'Platinum',
                'slug' => 'platinum',
                'original_price' => 7999,
                'discounted_price' => 5499,
                'discount' => 'Best Value',
                'duration' => '45 Days',
                'highlight' => true,
                'badge' => 'POPULAR',
                'tagline' => 'Steady growth, better engagement, and consistent visibility.',
                'button_text' => 'Choose Platinum',
                'button_class' => 'btn-warning text-dark fw-bold',
                'border_class' => 'border-warning',
                'features' => [
                    ['text' => '3 Reels Shoot', 'included' => true],
                    ['text' => '10 Feed Posts + 10 Stories', 'included' => true],
                    ['text' => 'Content Planning', 'included' => true],
                    ['text' => 'Caption + Hashtag Strategy', 'included' => true],
                    ['text' => 'Instagram Management – 45 Days', 'included' => true],
                    ['text' => 'Content Calendar', 'included' => true],
                    ['text' => 'Caption + CTA Optimization', 'included' => true],
                    ['text' => 'Comments + Basic DM Handling', 'included' => true],
                    ['text' => 'Basic Niche & Direction Clarity', 'included' => true],
                    ['text' => 'Expected Organic Reach & Engagement Improvement', 'included' => true],

                    ['text' => 'Performance Tracking & Optimization', 'included' => false],
                    ['text' => 'Full Brand Positioning Strategy', 'included' => false],
                    ['text' => 'Advanced Conversion Funnels / Campaign Strategy', 'included' => false],
                ],
            ],

            self::GOLD => [
                'title' => 'Gold',
                'slug' => 'gold',
                'original_price' => 11999,
                'discounted_price' => 7999,
                'discount' => 'Premium',
                'duration' => '90 Days (3 Months)',
                'highlight' => false,
                'badge' => null,
                'tagline' => 'Built for long-term brand authority and real business growth.',
                'button_text' => 'Choose Gold',
                'button_class' => 'btn-outline-dark',
                'border_class' => 'border-dark',
                'features' => [
                    ['text' => '6 Reels Shoot', 'included' => true],
                    ['text' => '15 Feed Posts + 15 Stories', 'included' => true],
                    ['text' => 'Content Planning', 'included' => true],
                    ['text' => 'Caption + Hashtag Strategy', 'included' => true],
                    ['text' => 'Instagram Management – 90 Days', 'included' => true],
                    ['text' => 'Content Calendar', 'included' => true],
                    ['text' => 'Caption + CTA Optimization', 'included' => true],
                    ['text' => 'Comments + DM Handling', 'included' => true],
                    ['text' => 'Performance Tracking & Optimization', 'included' => true],
                    ['text' => 'Brand Positioning Focus', 'included' => true],
                    ['text' => 'Advanced Content Strategy', 'included' => true],
                ],
            ],
        };
    }

    /**
     * Get all plans (for pricing page)
     */
    public static function getAllPlans(): array
    {
        return array_map(fn($plan) => $plan->details(), self::cases());
    }

    /**
     * Get plan by slug
     */
    public static function getPlanByTitle(string $title): ?array
    {
        $slug = strtolower(str_replace(' ', '_', $title));
        $plan = collect(self::cases())
            ->first(fn($case) => $case->value === $slug);
        return $plan?->details();
    }

    /**
     * Get only plan slugs
     */
    public static function getSlugs(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}

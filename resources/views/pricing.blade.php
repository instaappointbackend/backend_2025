@php
    /*
    $plans = [
        [
            'title' => 'Basic',
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
        [
            'title' => 'Standard',
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
        [
            'title' => 'Super Saving',
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
    ];*/

    use App\Enums\PlanEnum;
    $plans = PlanEnum::getAllPlans();
@endphp

<style>
    .pricing-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .pricing-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.15);
    }

    .pricing-card .card-body {
        padding: 1rem 1rem;
        /* reduced vertical padding */
    }

    .plan-title {
        font-size: 1.5rem;
        font-weight: 600;
    }

    .feature-list {
        list-style: none;
        padding: 0;
        margin: 0;
        margin-left: 32px;
        /* Added left margin */

    }

    .feature-list li {
        margin: 0.4rem 0;
        /* reduced margin */
        font-size: 0.9rem;
    }

    .feature-list i {
        margin-right: 10px;
    }

    .included {
        color: #198754;
    }

    .excluded {
        color: #dc3545;
    }

    .badge-popular {
        background-color: gold;
        color: #000;
        font-size: 0.75rem;
        padding: 0.25em 0.5em;
        border-radius: 0.25rem;
        font-weight: 600;
        margin-left: 8px;
    }
</style>

<section class="bg-light gap-3" id="pricing">
    <div class="container py-2 mb-2 text-center">
        <h1 class="mb-3">Choose Your Plan</h1>
        <p class="text-muted mb-4">Flexible pricing tailored to your needs</p>

        <div class="row g-4 justify-content-center">
            @foreach ($plans as $plan)
                <div class="col-md-4">
                    <div class="card pricing-card h-100 border {{ $plan['border_class'] }}">
                        <div class="card-body">
                            <h5 class="plan-title">
                                {{ $plan['title'] }}
                                @if (!empty($plan['highlight']) && $plan['highlight'])
                                    <span class="badge-popular">{{ $plan['badge'] ?? '' }}</span>
                                @endif
                            </h5>
                            <div class="text-center mt-2 mb-2">
                                <span
                                    class="text-muted text-decoration-line-through me-2">₹{{ $plan['original_price'] }}</span>
                                <span
                                    class="fw-bold {{ $plan['border_class'] === 'border-warning' ? 'text-warning' : 'text-success' }} me-2">
                                    ₹{{ $plan['discounted_price'] }}
                                </span>
                                <span class="badge bg-danger">{{ $plan['discount'] }}</span>
                            </div>
                            <p class="text-muted small mb-3">{{ $plan['duration'] }}</p>
                            <ul class="feature-list text-start">
                                @foreach ($plan['features'] as $feature)
                                    <li>
                                        <i
                                            class="fas {{ $feature['included'] ? 'fa-check included' : 'fa-times excluded' }}"></i>
                                        {{ $feature['text'] }}
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('subscription.form', ['plan' => $plan['slug']]) }}"
                                class="btn mt-3 {{ $plan['button_class'] }}">{{ $plan['button_text'] }}</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

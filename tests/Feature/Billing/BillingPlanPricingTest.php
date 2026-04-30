<?php

namespace Tests\Feature\Billing;

use App\Models\Plan;
use Database\Seeders\BillingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPlanPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_plans_match_stripe_amounts_without_trials(): void
    {
        $this->seed(BillingSeeder::class);

        $basicPlan = Plan::query()->where('slug', 'basic')->firstOrFail();
        $proPlan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->assertSame('10.00', $basicPlan->price);
        $this->assertNull($basicPlan->trial_days);
        $this->assertSame('20.00', $proPlan->price);
        $this->assertNull($proPlan->trial_days);
    }
}

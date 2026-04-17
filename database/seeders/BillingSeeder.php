<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class BillingSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Starter access for small catalogs.',
                'stripe_price_id' => null,
                'stripe_product_id' => null,
                'price' => 0,
                'currency' => 'usd',
                'interval' => 'month',
                'trial_days' => null,
                'limits' => [
                    'flipbooks' => 2,
                    'storage_mb' => 100,
                ],
                'features' => [
                    'branding' => false,
                    'analytics' => false,
                ],
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Growth plan for active stores.',
                'stripe_price_id' => env('STRIPE_BASIC_PRICE_ID'),
                'stripe_product_id' => env('STRIPE_BASIC_PRODUCT_ID'),
                'price' => 29,
                'currency' => 'usd',
                'interval' => 'month',
                'trial_days' => 14,
                'limits' => [
                    'flipbooks' => 10,
                    'storage_mb' => 1024,
                ],
                'features' => [
                    'branding' => false,
                    'analytics' => true,
                ],
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Advanced plan for high-volume publishing.',
                'stripe_price_id' => env('STRIPE_PRO_PRICE_ID'),
                'stripe_product_id' => env('STRIPE_PRO_PRODUCT_ID'),
                'price' => 99,
                'currency' => 'usd',
                'interval' => 'month',
                'trial_days' => 14,
                'limits' => [
                    'flipbooks' => null,
                    'storage_mb' => 10240,
                ],
                'features' => [
                    'branding' => true,
                    'analytics' => true,
                ],
                'is_active' => true,
                'sort_order' => 20,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}

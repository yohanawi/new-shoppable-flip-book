<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class BillingManager
{
    public function activePlans()
    {
        if (!Schema::hasTable('plans')) {
            return collect([$this->fallbackFreePlan()]);
        }

        $plans = Plan::query()->active()->get();

        return $plans->isEmpty() ? collect([$this->fallbackFreePlan()]) : $plans;
    }

    public function defaultPlan(): Plan
    {
        return $this->activePlans()->first();
    }

    public function planFor(?User $user): Plan
    {
        if (!$user || !Schema::hasTable('plans')) {
            return $this->fallbackFreePlan();
        }

        if ($user->isAdmin()) {
            return $this->defaultPlan();
        }

        $subscription = $this->subscriptionFor($user);

        if ($subscription?->plan) {
            return $subscription->plan;
        }

        $freePlan = Plan::query()->active()->where('slug', 'free')->first();

        return $freePlan ?: $this->fallbackFreePlan();
    }

    public function subscriptionFor(User $user)
    {
        if (!Schema::hasTable('subscriptions')) {
            return null;
        }

        $subscription = $user->subscription('default');

        if (!$subscription) {
            return null;
        }

        if (!$subscription->valid() && !$subscription->onGracePeriod()) {
            return null;
        }

        return $subscription->loadMissing('plan');
    }

    public function usageFor(User $user): array
    {
        $flipbooksCount = $user->catalogPdfs()->count();
        $storageBytes = (int) $user->catalogPdfs()->sum('size');

        return [
            'flipbooks_count' => $flipbooksCount,
            'storage_bytes' => $storageBytes,
        ];
    }

    public function limitFor(User $user, string $key): mixed
    {
        return $this->planFor($user)->limit($key);
    }

    public function hasFeature(User $user, string $feature): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->planFor($user)->hasFeature($feature);
    }

    public function canCreateFlipbook(User $user, int $incomingFileBytes = 0): array
    {
        if ($user->isAdmin()) {
            return [
                'allowed' => true,
                'message' => null,
            ];
        }

        $plan = $this->planFor($user);
        $usage = $this->usageFor($user);

        $flipbookLimit = $plan->limit('flipbooks');
        if ($flipbookLimit !== null && $usage['flipbooks_count'] >= (int) $flipbookLimit) {
            return [
                'allowed' => false,
                'message' => 'You have reached your flipbook limit for the current plan.',
            ];
        }

        $storageLimitBytes = $this->storageLimitBytes($plan);
        if ($storageLimitBytes !== null && ($usage['storage_bytes'] + $incomingFileBytes) > $storageLimitBytes) {
            return [
                'allowed' => false,
                'message' => 'Uploading this file would exceed your plan storage limit.',
            ];
        }

        return [
            'allowed' => true,
            'message' => null,
        ];
    }

    public function storageLimitBytes(Plan $plan): ?int
    {
        $storageMb = $plan->limit('storage_mb');

        if ($storageMb === null) {
            return null;
        }

        return (int) $storageMb * 1024 * 1024;
    }

    public function formatBytes(?int $bytes): string
    {
        if ($bytes === null) {
            return 'Unlimited';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = max(0, (float) $bytes);
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return number_format($value, $unit === 0 ? 0 : 2) . ' ' . $units[$unit];
    }

    private function fallbackFreePlan(): Plan
    {
        return new Plan([
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Starter plan',
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
        ]);
    }
}

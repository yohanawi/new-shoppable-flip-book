<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingInvoice;
use App\Models\BillingTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class AdminBillingController extends Controller
{
    public function index()
    {
        $metrics = [
            'monthly_revenue' => (int) BillingInvoice::query()
                ->where('status', 'paid')
                ->where('paid_at', '>=', now()->subDays(30))
                ->sum('amount_paid'),
            'active_subscriptions' => Subscription::query()
                ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
                ->count(),
            'churned_subscriptions' => Subscription::query()
                ->whereNotNull('ends_at')
                ->where('ends_at', '>=', now()->subDays(30))
                ->count(),
            'failed_payments' => BillingInvoice::query()->where('status', 'failed')->count(),
        ];

        return view('pages.apps.billing.admin', [
            'metrics' => $metrics,
            'plans' => Plan::query()->orderBy('sort_order')->orderBy('id')->get(),
            'subscriptions' => Subscription::query()->with(['owner', 'plan'])->latest()->limit(20)->get(),
            'invoices' => BillingInvoice::query()->with(['user', 'subscription.plan'])->latest()->limit(20)->get(),
            'transactions' => BillingTransaction::query()->with(['user', 'invoice'])->latest()->limit(20)->get(),
        ]);
    }

    public function storePlan(Request $request): RedirectResponse
    {
        Plan::create($this->validatedPlanData($request));

        return redirect()->route('admin.billing.index')->with('success', 'Billing plan created successfully.');
    }

    public function updatePlan(Request $request, Plan $plan): RedirectResponse
    {
        $plan->update($this->validatedPlanData($request, $plan));

        return redirect()->route('admin.billing.index')->with('success', 'Billing plan updated successfully.');
    }

    public function destroyPlan(Plan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->exists()) {
            return redirect()->route('admin.billing.index')->withErrors(['admin_billing' => 'Plans with subscription history cannot be deleted.']);
        }

        $plan->delete();

        return redirect()->route('admin.billing.index')->with('success', 'Billing plan deleted successfully.');
    }

    public function swapSubscription(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ]);

        $plan = Plan::query()->findOrFail($validated['plan_id']);

        if ($plan->isFree()) {
            $subscription->cancel();
            $subscription->forceFill(['plan_id' => null])->save();

            return redirect()->route('admin.billing.index')->with('success', 'Subscription cancellation scheduled and customer will fall back to the free plan.');
        }

        if (blank($plan->stripe_price_id)) {
            return redirect()->route('admin.billing.index')->withErrors(['admin_billing' => 'Selected plan does not have a Stripe price ID.']);
        }

        try {
            $subscription->swap($plan->stripe_price_id);
            $subscription->forceFill(['plan_id' => $plan->id])->save();

            return redirect()->route('admin.billing.index')->with('success', 'Subscription plan updated successfully.');
        } catch (Throwable) {
            return redirect()->route('admin.billing.index')->withErrors(['admin_billing' => 'Unable to update the Stripe subscription for the selected customer.']);
        }
    }

    public function cancelSubscription(Subscription $subscription): RedirectResponse
    {
        $subscription->cancel();

        return redirect()->route('admin.billing.index')->with('success', 'Subscription cancellation scheduled.');
    }

    private function validatedPlanData(Request $request, ?Plan $plan = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('plans', 'slug')->ignore($plan?->id)],
            'description' => ['nullable', 'string'],
            'stripe_price_id' => ['nullable', 'string', 'max:255', Rule::unique('plans', 'stripe_price_id')->ignore($plan?->id)],
            'stripe_product_id' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'interval' => ['required', 'string', 'in:month,year'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'limits.flipbooks' => ['nullable', 'integer', 'min:1'],
            'limits.storage_mb' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'features.branding' => ['nullable', 'boolean'],
            'features.analytics' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'stripe_price_id' => $validated['stripe_price_id'] ?: null,
            'stripe_product_id' => $validated['stripe_product_id'] ?: null,
            'price' => $validated['price'],
            'currency' => strtolower((string) $validated['currency']),
            'interval' => $validated['interval'],
            'trial_days' => $validated['trial_days'] ?? null,
            'limits' => [
                'flipbooks' => $validated['limits']['flipbooks'] ?? null,
                'storage_mb' => $validated['limits']['storage_mb'] ?? null,
            ],
            'features' => [
                'branding' => $request->boolean('features.branding'),
                'analytics' => $request->boolean('features.analytics'),
            ],
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];
    }
}

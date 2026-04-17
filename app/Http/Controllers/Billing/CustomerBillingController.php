<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\BillingManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Throwable;

class CustomerBillingController extends Controller
{
    public function index(Request $request, BillingManager $billingManager)
    {
        $user = $request->user();
        $currentPlan = $billingManager->planFor($user);
        $subscription = $billingManager->subscriptionFor($user);
        $usage = $billingManager->usageFor($user);
        $plans = $billingManager->activePlans();
        $stripeConfigured = filled(config('cashier.key')) && filled(config('cashier.secret'));

        $setupIntent = null;
        $cashierInvoices = collect();
        $paymentMethods = collect();
        $defaultPaymentMethod = null;
        $upcomingInvoice = null;

        if ($stripeConfigured) {
            $setupIntent = rescue(fn() => $user->createSetupIntent(), null, false);
            $cashierInvoices = rescue(fn() => $user->invoicesIncludingPending(['limit' => 12]), collect(), false);
            $paymentMethods = rescue(fn() => $user->paymentMethods(), collect(), false);
            $defaultPaymentMethod = rescue(fn() => $user->defaultPaymentMethod(), null, false);
            $upcomingInvoice = rescue(fn() => $subscription ? $subscription->upcomingInvoice() : null, null, false);
        }

        $transactions = $user->billingTransactions()->with('invoice')->limit(12)->get();

        return view('pages.apps.billing.customer', [
            'currentPlan' => $currentPlan,
            'subscription' => $subscription,
            'usage' => $usage,
            'plans' => $plans,
            'cashierInvoices' => $cashierInvoices,
            'paymentMethods' => $paymentMethods,
            'defaultPaymentMethod' => $defaultPaymentMethod,
            'setupIntent' => $setupIntent,
            'stripeConfigured' => $stripeConfigured,
            'stripePublicKey' => config('cashier.key'),
            'upcomingInvoice' => $upcomingInvoice,
            'transactions' => $transactions,
            'billingManager' => $billingManager,
        ]);
    }

    public function subscribe(Request $request, Plan $plan, BillingManager $billingManager)
    {
        abort_unless($plan->is_active, 404);

        $user = $request->user();
        $subscription = $billingManager->subscriptionFor($user);

        if ($plan->isFree()) {
            if ($subscription) {
                $subscription->cancel();
            }

            return redirect()
                ->route('billing.index')
                ->with('success', 'Your paid subscription will end at the close of the current billing period.');
        }

        if (blank(config('cashier.secret')) || blank(config('cashier.key')) || blank($plan->stripe_price_id)) {
            return redirect()
                ->route('billing.index')
                ->withErrors(['billing' => 'Stripe billing is not configured for the selected plan.']);
        }

        if ($subscription) {
            if ((int) $subscription->plan_id === (int) $plan->id) {
                return redirect()->route('billing.index')->with('status', 'You are already subscribed to this plan.');
            }

            try {
                $subscription->swap($plan->stripe_price_id);
                $subscription->forceFill(['plan_id' => $plan->id])->save();

                return redirect()->route('billing.index')->with('success', 'Subscription updated successfully.');
            } catch (IncompletePayment $exception) {
                return redirect()->route('cashier.payment', [$exception->payment->id, 'redirect' => route('billing.index')]);
            } catch (Throwable) {
                return redirect()
                    ->route('billing.index')
                    ->withErrors(['billing' => 'Unable to change the subscription automatically. Add a valid payment method or use the billing portal.']);
            }
        }

        $builder = $user->newSubscription('default', $plan->stripe_price_id)
            ->withMetadata(['plan_id' => (string) $plan->id]);

        if ($plan->trial_days) {
            $builder->trialDays((int) $plan->trial_days);
        }

        return $builder->checkout([
            'success_url' => route('billing.index', ['checkout' => 'success']),
            'cancel_url' => route('billing.index', ['checkout' => 'cancelled']),
        ]);
    }

    public function billingPortal(Request $request)
    {
        $user = $request->user();

        if (blank(config('cashier.secret')) || !$user->hasStripeId()) {
            return redirect()
                ->route('billing.index')
                ->withErrors(['billing' => 'Billing portal is not available until Stripe is configured and a customer profile exists.']);
        }

        return $user->redirectToBillingPortal(route('billing.index'));
    }

    public function storePaymentMethod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (blank(config('cashier.secret'))) {
            return redirect()->route('billing.index')->withErrors(['billing' => 'Stripe billing is not configured.']);
        }

        $user->createOrGetStripeCustomer();
        $user->addPaymentMethod($validated['payment_method']);
        $user->updateDefaultPaymentMethod($validated['payment_method']);

        return redirect()->route('billing.index')->with('success', 'Payment method saved successfully.');
    }

    public function setDefaultPaymentMethod(Request $request, string $paymentMethod): RedirectResponse
    {
        $request->user()->updateDefaultPaymentMethod($paymentMethod);

        return redirect()->route('billing.index')->with('success', 'Default payment method updated.');
    }

    public function destroyPaymentMethod(Request $request, string $paymentMethod): RedirectResponse
    {
        $request->user()->deletePaymentMethod($paymentMethod);
        rescue(fn() => $request->user()->updateDefaultPaymentMethodFromStripe(), null, false);

        return redirect()->route('billing.index')->with('success', 'Payment method removed.');
    }

    public function cancelSubscription(Request $request, BillingManager $billingManager): RedirectResponse
    {
        $subscription = $billingManager->subscriptionFor($request->user());

        if (!$subscription) {
            return redirect()->route('billing.index')->withErrors(['billing' => 'There is no active paid subscription to cancel.']);
        }

        $subscription->cancel();

        return redirect()->route('billing.index')->with('success', 'Subscription cancellation scheduled for period end.');
    }

    public function resumeSubscription(Request $request, BillingManager $billingManager): RedirectResponse
    {
        $subscription = $billingManager->subscriptionFor($request->user());

        if (!$subscription || !$subscription->onGracePeriod()) {
            return redirect()->route('billing.index')->withErrors(['billing' => 'Only subscriptions in grace period can be resumed.']);
        }

        $subscription->resume();

        return redirect()->route('billing.index')->with('success', 'Subscription resumed successfully.');
    }

    public function downloadInvoice(Request $request, string $invoiceId)
    {
        return $request->user()->downloadInvoice(
            $invoiceId,
            [
                'vendor' => 'Flipbook',
                'product' => 'Flipbook Subscription',
            ],
            $invoiceId . '.pdf'
        );
    }
}

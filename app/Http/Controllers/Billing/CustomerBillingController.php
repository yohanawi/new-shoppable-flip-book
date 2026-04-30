<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingInvoice;
use App\Models\Plan;
use App\Services\BillingManager;
use Dompdf\Dompdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Throwable;

class CustomerBillingController extends Controller
{
    public function index(Request $request, BillingManager $billingManager): View
    {
        return view('pages.apps.billing.customer', $this->billingViewData($request, $billingManager, 'overview'));
    }

    public function plans(Request $request, BillingManager $billingManager): View|RedirectResponse
    {
        if ($checkoutRedirect = $this->handleCheckoutReturn($request)) {
            return $checkoutRedirect;
        }

        return view('pages.apps.billing.customer-plans', $this->billingViewData($request, $billingManager, 'plans'));
    }

    public function paymentMethods(Request $request, BillingManager $billingManager): View
    {
        return view('pages.apps.billing.payment-methods', $this->billingViewData($request, $billingManager, 'payment-methods'));
    }

    public function invoices(Request $request, BillingManager $billingManager): View
    {
        return view('pages.apps.billing.invoices', $this->billingViewData($request, $billingManager, 'invoices'));
    }

    private function billingViewData(Request $request, BillingManager $billingManager, string $activeSection): array
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
            $cashierInvoices = collect(rescue(fn() => $user->invoicesIncludingPending(['limit' => 12]), [], false));
            $paymentMethods = collect(rescue(fn() => $user->paymentMethods(), [], false));
            $defaultPaymentMethod = rescue(fn() => $user->defaultPaymentMethod(), null, false);
            $upcomingInvoice = rescue(fn() => $subscription ? $subscription->upcomingInvoice() : null, null, false);
        }

        $transactions = $user->billingTransactions()->with('invoice')->limit(12)->get();
        $latestPaymentRequest = $user->billingPaymentRequests()->with(['plan', 'invoice'])->first();
        $openPaymentRequest = $user->billingPaymentRequests()->with('plan')->open()->first();
        $paymentRequestsByPlan = $user->billingPaymentRequests()
            ->with('plan')
            ->get()
            ->unique('plan_id')
            ->keyBy('plan_id');
        $recentPaymentRequests = $user->billingPaymentRequests()->with(['plan', 'invoice'])->limit(5)->get();

        return [
            'currentPlan' => $currentPlan,
            'subscription' => $subscription,
            'usage' => $usage,
            'plans' => $plans,
            'cashierInvoices' => $cashierInvoices,
            'latestInvoice' => $cashierInvoices->first(),
            'recentInvoices' => $cashierInvoices->take(5),
            'paymentMethods' => $paymentMethods,
            'defaultPaymentMethod' => $defaultPaymentMethod,
            'setupIntent' => $setupIntent,
            'stripeConfigured' => $stripeConfigured,
            'stripePublicKey' => config('cashier.key'),
            'upcomingInvoice' => $upcomingInvoice,
            'transactions' => $transactions,
            'latestPaymentRequest' => $latestPaymentRequest,
            'openPaymentRequest' => $openPaymentRequest,
            'paymentRequestsByPlan' => $paymentRequestsByPlan,
            'recentPaymentRequests' => $recentPaymentRequests,
            'billingActiveSection' => $activeSection,
            'checkoutState' => $request->query('checkout'),
            'billingManager' => $billingManager,
        ];
    }

    public function subscribe(Request $request, Plan $plan, BillingManager $billingManager)
    {
        abort_unless($plan->is_active, 404);

        $user = $request->user();
        $subscription = $billingManager->subscriptionFor($user);

        if ($plan->isFree()) {
            if ($subscription) {
                if ($subscription->isManualBilling()) {
                    $subscription->cancelManual();
                } else {
                    $subscription->cancel();
                }
            }

            return redirect()
                ->route('billing.plans')
                ->with('success', 'Your paid subscription will end at the close of the current billing period.');
        }

        if (blank(config('cashier.secret')) || blank(config('cashier.key')) || blank($plan->stripe_price_id)) {
            return redirect()
                ->route('billing.plans')
                ->withErrors(['billing' => 'Stripe billing is not configured for the selected plan.']);
        }

        if ($subscription) {
            if ((int) $subscription->plan_id === (int) $plan->id) {
                return redirect()->route('billing.plans')->with('status', 'You are already subscribed to this plan.');
            }

            if ($subscription->isManualBilling()) {
                return redirect()
                    ->route('billing.plans')
                    ->withErrors(['billing' => 'This subscription was activated from a manual payment request. Submit a new manual payment request to change plans.']);
            }

            try {
                $subscription->swap($plan->stripe_price_id);
                $subscription->forceFill(['plan_id' => $plan->id])->save();

                return redirect()->route('billing.plans')->with('success', 'Subscription updated successfully.');
            } catch (IncompletePayment $exception) {
                return redirect()->route('cashier.payment', [$exception->payment->id, 'redirect' => route('billing.plans')]);
            } catch (Throwable) {
                return redirect()
                    ->route('billing.plans')
                    ->withErrors(['billing' => 'Unable to change the subscription automatically. Add a valid payment method or use the billing portal.']);
            }
        }

        $builder = $user->newSubscription('default', $plan->stripe_price_id)
            ->withMetadata(['plan_id' => (string) $plan->id]);

        if ($plan->trial_days) {
            $builder->trialDays((int) $plan->trial_days);
        }

        return $builder->checkout([
            'success_url' => route('billing.plans', ['checkout' => 'success']) . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.plans', ['checkout' => 'cancelled']),
        ]);
    }

    private function handleCheckoutReturn(Request $request): ?RedirectResponse
    {
        if ($request->query('checkout') !== 'success') {
            return null;
        }

        $checkoutSessionId = $request->query('session_id');
        if (!$checkoutSessionId) {
            return redirect()
                ->route('billing.plans', ['checkout' => 'failed'])
                ->withErrors(['billing' => 'Payment was completed in Stripe, but the app did not receive the checkout session details needed to activate your subscription.']);
        }

        $subscription = app('App\\Services\\BillingStripeSyncService')
            ->syncCheckoutSession((string) $checkoutSessionId, $request->user());
        if (!$subscription || !$subscription->valid()) {
            return redirect()
                ->route('billing.plans', ['checkout' => 'failed'])
                ->withErrors(['billing' => 'Payment was completed in Stripe, but the local subscription could not be activated automatically.']);
        }

        return redirect()
            ->route('billing.plans')
            ->with('success', 'Payment successful. Your subscription is now active.');
    }

    public function billingPortal(Request $request)
    {
        $user = $request->user();

        if (blank(config('cashier.secret')) || !$user->hasStripeId()) {
            return redirect()
                ->route('billing.payment-methods.index')
                ->withErrors(['billing' => 'Billing portal is not available until Stripe is configured and a customer profile exists.']);
        }

        return $user->redirectToBillingPortal(route('billing.payment-methods.index'));
    }

    public function storePaymentMethod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (blank(config('cashier.secret'))) {
            return redirect()->route('billing.payment-methods.index')->withErrors(['billing' => 'Stripe billing is not configured.']);
        }

        $user->createOrGetStripeCustomer();
        $user->addPaymentMethod($validated['payment_method']);
        $user->updateDefaultPaymentMethod($validated['payment_method']);

        return redirect()->route('billing.payment-methods.index')->with('success', 'Payment method saved successfully.');
    }

    public function setDefaultPaymentMethod(Request $request, string $paymentMethod): RedirectResponse
    {
        $request->user()->updateDefaultPaymentMethod($paymentMethod);

        return redirect()->route('billing.payment-methods.index')->with('success', 'Default payment method updated.');
    }

    public function destroyPaymentMethod(Request $request, string $paymentMethod): RedirectResponse
    {
        $request->user()->deletePaymentMethod($paymentMethod);
        rescue(fn() => $request->user()->updateDefaultPaymentMethodFromStripe(), null, false);

        return redirect()->route('billing.payment-methods.index')->with('success', 'Payment method removed.');
    }

    public function cancelSubscription(Request $request, BillingManager $billingManager): RedirectResponse
    {
        $subscription = $billingManager->subscriptionFor($request->user());

        if (!$subscription) {
            return redirect()->route('billing.plans')->withErrors(['billing' => 'There is no active paid subscription to cancel.']);
        }

        if ($subscription->isManualBilling()) {
            $subscription->cancelManual();
        } else {
            $subscription->cancel();
        }

        return redirect()->route('billing.plans')->with('success', 'Subscription cancellation scheduled for period end.');
    }

    public function resumeSubscription(Request $request, BillingManager $billingManager): RedirectResponse
    {
        $subscription = $billingManager->subscriptionFor($request->user());

        if (!$subscription || !$subscription->onGracePeriod()) {
            return redirect()->route('billing.plans')->withErrors(['billing' => 'Only subscriptions in grace period can be resumed.']);
        }

        if ($subscription->isManualBilling()) {
            $subscription->resumeManual();
        } else {
            $subscription->resume();
        }

        return redirect()->route('billing.plans')->with('success', 'Subscription resumed successfully.');
    }

    public function downloadInvoice(Request $request, string $invoiceId)
    {
        $invoice = BillingInvoice::query()
            ->with(['user', 'subscription.plan'])
            ->where('user_id', $request->user()->id)
            ->where(function ($query) use ($invoiceId) {
                $query->whereKey($invoiceId)
                    ->orWhere('stripe_invoice_id', $invoiceId);
            })
            ->first();

        if ($invoice && blank($invoice->stripe_invoice_id)) {
            $pdf = new Dompdf(['isRemoteEnabled' => true]);
            $pdf->loadHtml(view('pdf.billing-invoice', ['invoice' => $invoice])->render());
            $pdf->setPaper('A4');
            $pdf->render();

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . ($invoice->number ?: 'invoice-' . $invoice->id) . '.pdf"',
            ]);
        }

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

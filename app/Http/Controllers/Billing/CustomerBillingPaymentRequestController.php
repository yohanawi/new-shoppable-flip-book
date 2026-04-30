<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingPaymentRequest;
use App\Models\Plan;
use App\Services\BillingManager;
use App\Services\Notifications\BillingPaymentRequestNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerBillingPaymentRequestController extends Controller
{
    public function create(Request $request, BillingManager $billingManager): View|RedirectResponse
    {
        $user = $request->user();
        $plans = $billingManager->activePlans()->filter(fn(Plan $plan) => !$plan->isFree())->values();
        $openRequest = $user->billingPaymentRequests()->with('plan')->open()->latest('submitted_at')->first();

        if ($openRequest) {
            return redirect()
                ->route('billing.payments.show', $openRequest)
                ->with('status', 'You already have a payment request under review.');
        }

        $selectedPlan = null;
        if ($request->filled('plan')) {
            $selectedPlan = $plans->firstWhere('id', (int) $request->integer('plan'));
        }

        return view('pages.apps.billing.payment-request', [
            'currentPlan' => $billingManager->planFor($user),
            'plans' => $plans,
            'selectedPlan' => $selectedPlan,
        ]);
    }

    public function store(Request $request, BillingPaymentRequestNotificationService $notificationService): RedirectResponse
    {
        $user = $request->user();
        $existingRequest = $user->billingPaymentRequests()->open()->latest('submitted_at')->first();

        if ($existingRequest) {
            return redirect()
                ->route('billing.payments.show', $existingRequest)
                ->with('status', 'Your latest payment request is already awaiting review.');
        }

        $validated = $this->validatePaymentRequest($request, true);
        $plan = Plan::query()->where('is_active', true)->findOrFail($validated['plan_id']);

        if ($plan->isFree()) {
            return redirect()
                ->route('billing.index')
                ->withErrors(['billing' => 'Free plan changes do not require a payment request.']);
        }

        $receipt = $request->file('receipt');
        $paymentRequest = BillingPaymentRequest::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => BillingPaymentRequest::GATEWAY_MANUAL,
            'currency' => strtolower((string) $plan->currency),
            'amount' => $plan->price,
            'transaction_reference' => $validated['transaction_reference'],
            'receipt_disk' => 'public',
            'receipt_path' => $receipt->store('billing/payment-requests', 'public'),
            'receipt_name' => $receipt->getClientOriginalName(),
            'status' => BillingPaymentRequest::STATUS_PENDING,
            'customer_note' => $validated['customer_note'] ?? null,
            'submitted_at' => now(),
        ]);

        $notificationService->sendSubmitted($paymentRequest->loadMissing('user', 'plan'));

        return redirect()
            ->route('billing.payments.show', $paymentRequest)
            ->with('success', 'Payment request submitted successfully.');
    }

    public function show(Request $request, BillingPaymentRequest $paymentRequest): View
    {
        abort_unless((int) $paymentRequest->user_id === (int) $request->user()->id, 404);

        return view('pages.apps.billing.payment-status', [
            'paymentRequest' => $paymentRequest->loadMissing(['plan', 'invoice', 'reviewer']),
        ]);
    }

    public function receipt(Request $request, BillingPaymentRequest $paymentRequest): BinaryFileResponse
    {
        abort_unless((int) $paymentRequest->user_id === (int) $request->user()->id, 404);
        abort_unless($paymentRequest->hasReceipt(), 404);

        $disk = Storage::disk($paymentRequest->receipt_disk);
        $fileName = $paymentRequest->receipt_name ?: basename((string) $paymentRequest->receipt_path);

        return response()->file($disk->path($paymentRequest->receipt_path), [
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $fileName) . '"',
        ]);
    }

    public function history(Request $request): View
    {
        return view('pages.apps.billing.history', [
            'paymentRequests' => $request->user()
                ->billingPaymentRequests()
                ->with(['plan', 'invoice'])
                ->latest('submitted_at')
                ->paginate(12),
        ]);
    }

    public function resubmit(Request $request, BillingPaymentRequest $paymentRequest, BillingPaymentRequestNotificationService $notificationService): RedirectResponse
    {
        abort_unless((int) $paymentRequest->user_id === (int) $request->user()->id, 404);

        if (!$paymentRequest->isRejected()) {
            return redirect()
                ->route('billing.payments.show', $paymentRequest)
                ->withErrors(['billing' => 'Only rejected payment requests can be resubmitted.']);
        }

        $validated = $this->validatePaymentRequest($request, !$paymentRequest->receipt_path);
        $plan = Plan::query()->where('is_active', true)->findOrFail($validated['plan_id']);

        if ($plan->isFree()) {
            return redirect()
                ->route('billing.index')
                ->withErrors(['billing' => 'Free plan changes do not require a payment request.']);
        }

        $attributes = [
            'plan_id' => $plan->id,
            'gateway' => BillingPaymentRequest::GATEWAY_MANUAL,
            'currency' => strtolower((string) $plan->currency),
            'amount' => $plan->price,
            'transaction_reference' => $validated['transaction_reference'],
            'status' => BillingPaymentRequest::STATUS_PENDING,
            'customer_note' => $validated['customer_note'] ?? null,
            'admin_note' => null,
            'submitted_at' => now(),
            'reviewed_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'reviewed_by' => null,
        ];

        if ($request->hasFile('receipt')) {
            if ($paymentRequest->receipt_disk && $paymentRequest->receipt_path) {
                Storage::disk($paymentRequest->receipt_disk)->delete($paymentRequest->receipt_path);
            }

            $receipt = $request->file('receipt');
            $attributes['receipt_disk'] = 'public';
            $attributes['receipt_path'] = $receipt->store('billing/payment-requests', 'public');
            $attributes['receipt_name'] = $receipt->getClientOriginalName();
        }

        $paymentRequest->update($attributes);
        $notificationService->sendResubmitted($paymentRequest->fresh(['user', 'plan']));

        return redirect()
            ->route('billing.payments.show', $paymentRequest)
            ->with('success', 'Payment request resubmitted successfully.');
    }

    private function validatePaymentRequest(Request $request, bool $receiptRequired): array
    {
        $receiptRules = ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,webp'];
        if ($receiptRequired) {
            $receiptRules[0] = 'required';
        }

        return $request->validate([
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')->where('is_active', true)],
            'transaction_reference' => ['required', 'string', 'max:255'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'receipt' => $receiptRules,
        ]);
    }
}

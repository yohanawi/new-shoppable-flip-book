<?php

namespace App\Http\Controllers\Apps;

use App\DataTables\UsersDataTable;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\BillingInvoice;
use App\Models\BillingPaymentRequest;
use App\Models\BillingTransaction;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(UsersDataTable $dataTable)
    {
        return $dataTable->render('pages/apps.user-management.users.list');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return view('pages/apps.user-management.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }

    /**
     * Show the user's profile page.
     */
    public function profile(Request $request)
    {
        return view('pages/apps.user-management.users.profile', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Show the user's settings page.
     */
    public function settings(Request $request)
    {
        $user = $request->user()->load(['roles', 'addresses']);

        $supportTicketQuery = $user->supportTickets();
        $invoiceQuery = $user->billingInvoices();
        $transactionQuery = $user->billingTransactions();
        $paymentRequestQuery = $user->billingPaymentRequests();

        return view('pages/apps.user-management.users.settings', [
            'user' => $user,
            'supportSummary' => [
                'total' => (clone $supportTicketQuery)->count(),
                'open' => (clone $supportTicketQuery)->whereIn('status', ['open', 'in_progress'])->count(),
                'closed' => (clone $supportTicketQuery)->where('status', 'closed')->count(),
            ],
            'billingSummary' => [
                'invoices_count' => (clone $invoiceQuery)->count(),
                'payment_requests_count' => (clone $paymentRequestQuery)->count(),
                'transactions_count' => (clone $transactionQuery)->count(),
                'amount_paid' => (int) (clone $invoiceQuery)->sum('amount_paid'),
            ],
            'recentSupportTickets' => (clone $supportTicketQuery)
                ->latest('updated_at')
                ->limit(5)
                ->get(),
            'recentInvoices' => (clone $invoiceQuery)
                ->latest('created_at')
                ->limit(5)
                ->get(),
            'recentPaymentRequests' => (clone $paymentRequestQuery)
                ->with('plan:id,name')
                ->latest('submitted_at')
                ->limit(5)
                ->get(),
            'recentTransactions' => (clone $transactionQuery)
                ->latest('processed_at')
                ->limit(5)
                ->get(),
            'activityLog' => $this->settingsActivityLog($user),
        ]);
    }

    /**
     * Update the user's settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        return match ($request->string('section')->value()) {
            'profile' => $this->updateProfileSettings($request),
            'email' => $this->updateEmailSettings($request),
            'password' => $this->updatePasswordSettings($request),
            default => redirect()
                ->route('account.settings')
                ->withErrors(['settings' => 'The requested settings section is not supported.']),
        };
    }

    private function updateProfileSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255', 'required_with:city,state,postal_code,country'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100', 'required_with:address_line_1,state,postal_code,country'],
            'state' => ['nullable', 'string', 'max:100', 'required_with:address_line_1,city,postal_code,country'],
            'postal_code' => ['nullable', 'string', 'max:30', 'required_with:address_line_1,city,state,country'],
            'country' => ['nullable', 'string', 'max:100', 'required_with:address_line_1,city,state,postal_code'],
        ]);

        $user = $request->user();
        $user->forceFill([
            'name' => $validated['name'],
        ])->save();

        $this->syncAddress($user, $validated);

        return redirect()
            ->route('account.settings', ['tab' => 'security'])
            ->with('settings_status', 'Profile details updated successfully.');
    }

    private function updateEmailSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($request->user()->getKey()),
            ],
        ]);

        $user = $request->user();

        if ($validated['email'] !== $user->email) {
            $user->forceFill([
                'email' => $validated['email'],
                'email_verified_at' => null,
            ])->save();

            if (method_exists($user, 'sendEmailVerificationNotification')) {
                $user->sendEmailVerificationNotification();
            }
        }

        return redirect()
            ->route('account.settings', ['tab' => 'security'])
            ->with('settings_status', 'Email address updated successfully.');
    }

    private function updatePasswordSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return redirect()
            ->route('account.settings', ['tab' => 'security'])
            ->with('settings_status', 'Password updated successfully.');
    }

    private function syncAddress(User $user, array $validated): void
    {
        $address = $user->addresses()->oldest('id')->first();
        $coreFields = [
            'address_line_1' => $validated['address_line_1'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'country' => $validated['country'] ?? null,
        ];

        $hasAddress = collect($coreFields)->filter(fn($value) => filled($value))->isNotEmpty();

        if (! $hasAddress) {
            if ($address) {
                $address->delete();
            }

            return;
        }

        $payload = [
            'address_line_1' => $coreFields['address_line_1'],
            'address_line_2' => $validated['address_line_2'] ?? null,
            'city' => $coreFields['city'],
            'state' => $coreFields['state'],
            'postal_code' => $coreFields['postal_code'],
            'country' => $coreFields['country'],
            'type' => $address?->type ?? 0,
        ];

        if ($address) {
            $address->fill($payload)->save();

            return;
        }

        $user->addresses()->create($payload);
    }

    private function settingsActivityLog(User $user): Collection
    {
        $activity = collect();

        if ($user->created_at) {
            $activity->push([
                'badge_class' => 'badge-light-info',
                'headline' => 'Account created',
                'details' => 'Your account was created and is ready to use.',
                'context' => 'Account',
                'timestamp' => $user->created_at,
            ]);
        }

        if ($user->last_login_at) {
            $activity->push([
                'badge_class' => 'badge-light-primary',
                'headline' => 'Last successful sign-in',
                'details' => $user->last_login_ip ? 'Signed in from IP ' . $user->last_login_ip . '.' : 'A successful sign-in was recorded.',
                'context' => 'Authentication',
                'timestamp' => $user->last_login_at,
            ]);
        }

        $supportActivity = $user->supportTickets()
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function (SupportTicket $ticket) {
                return [
                    'badge_class' => $ticket->status === 'closed' ? 'badge-light-success' : 'badge-light-warning',
                    'headline' => 'Support ticket ' . str_replace('_', ' ', $ticket->status),
                    'details' => $ticket->subject,
                    'context' => ucfirst((string) $ticket->priority) . ' priority',
                    'timestamp' => $ticket->updated_at,
                ];
            });

        $paymentRequestActivity = $user->billingPaymentRequests()
            ->with('plan:id,name')
            ->latest('submitted_at')
            ->limit(5)
            ->get()
            ->map(function (BillingPaymentRequest $paymentRequest) {
                return [
                    'badge_class' => match ($paymentRequest->status) {
                        BillingPaymentRequest::STATUS_APPROVED => 'badge-light-success',
                        BillingPaymentRequest::STATUS_REJECTED => 'badge-light-danger',
                        default => 'badge-light-warning',
                    },
                    'headline' => 'Payment request ' . $paymentRequest->statusLabel(),
                    'details' => $paymentRequest->requestNumber() . ($paymentRequest->plan ? ' for ' . $paymentRequest->plan->name : ''),
                    'context' => strtoupper((string) $paymentRequest->currency) . ' ' . number_format((float) $paymentRequest->amount, 2),
                    'timestamp' => $paymentRequest->submitted_at ?? $paymentRequest->created_at,
                ];
            });

        $invoiceActivity = $user->billingInvoices()
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function (BillingInvoice $invoice) {
                return [
                    'badge_class' => $invoice->status === 'paid' ? 'badge-light-success' : 'badge-light-info',
                    'headline' => 'Invoice ' . strtoupper((string) $invoice->status),
                    'details' => $invoice->number ?: 'Invoice #' . $invoice->getKey(),
                    'context' => strtoupper((string) $invoice->currency) . ' ' . number_format(((int) $invoice->amount_paid) / 100, 2),
                    'timestamp' => $invoice->paid_at ?? $invoice->created_at,
                ];
            });

        $transactionActivity = $user->billingTransactions()
            ->latest('processed_at')
            ->limit(5)
            ->get()
            ->map(function (BillingTransaction $transaction) {
                return [
                    'badge_class' => $transaction->status === 'succeeded' ? 'badge-light-success' : 'badge-light-danger',
                    'headline' => 'Transaction ' . strtoupper((string) $transaction->status),
                    'details' => $transaction->description ?: ucfirst((string) $transaction->type),
                    'context' => strtoupper((string) $transaction->currency) . ' ' . number_format(((int) $transaction->amount) / 100, 2),
                    'timestamp' => $transaction->processed_at ?? $transaction->created_at,
                ];
            });

        return $activity
            ->merge($supportActivity)
            ->merge($paymentRequestActivity)
            ->merge($invoiceActivity)
            ->merge($transactionActivity)
            ->filter(fn(array $item) => $item['timestamp'] !== null)
            ->sortByDesc('timestamp')
            ->values();
    }
}

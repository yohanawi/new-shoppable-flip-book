<?php

namespace Database\Seeders;

use App\Models\BillingInvoice;
use App\Models\BillingTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class BillingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(BillingSeeder::class);

        Role::firstOrCreate(['name' => 'customer']);

        $plans = Plan::query()
            ->whereIn('slug', ['basic', 'pro'])
            ->get()
            ->keyBy('slug');

        $basicPlan = $plans->get('basic');
        $proPlan = $plans->get('pro');

        if (!$basicPlan || !$proPlan) {
            throw new RuntimeException('BillingDemoSeeder requires the basic and pro billing plans.');
        }

        $now = CarbonImmutable::now();

        $activeUser = $this->upsertCustomer([
            'name' => 'Billing Active Demo',
            'email' => 'billing.active@demo.com',
            'stripe_id' => 'cus_demo_active',
            'pm_type' => 'visa',
            'pm_last_four' => '4242',
        ]);

        $activeSubscription = $this->upsertSubscription($activeUser, [
            'stripe_id' => 'sub_demo_active_001',
            'plan_id' => $basicPlan->id,
            'stripe_status' => 'active',
            'stripe_price' => $basicPlan->stripe_price_id ?: 'price_demo_basic_monthly',
            'quantity' => 1,
            'created_at' => $now->subDays(75),
            'updated_at' => $now->subDays(10),
        ]);

        $activeInvoiceOne = $this->upsertInvoice($activeUser, $activeSubscription, [
            'stripe_invoice_id' => 'in_demo_active_202604',
            'number' => 'INV-2026-0401',
            'currency' => 'usd',
            'amount_due' => 2900,
            'amount_paid' => 2900,
            'subtotal' => 2500,
            'tax' => 400,
            'status' => 'paid',
            'invoice_pdf_url' => 'https://example.com/invoices/in_demo_active_202604.pdf',
            'hosted_invoice_url' => 'https://example.com/invoices/in_demo_active_202604',
            'period_start' => $now->subDays(40),
            'period_end' => $now->subDays(10),
            'paid_at' => $now->subDays(10),
            'meta' => ['scenario' => 'active-paid', 'plan_slug' => 'basic'],
            'created_at' => $now->subDays(10),
            'updated_at' => $now->subDays(10),
        ]);

        $activeInvoiceTwo = $this->upsertInvoice($activeUser, $activeSubscription, [
            'stripe_invoice_id' => 'in_demo_active_202603',
            'number' => 'INV-2026-0328',
            'currency' => 'usd',
            'amount_due' => 2900,
            'amount_paid' => 2900,
            'subtotal' => 2500,
            'tax' => 400,
            'status' => 'paid',
            'invoice_pdf_url' => 'https://example.com/invoices/in_demo_active_202603.pdf',
            'hosted_invoice_url' => 'https://example.com/invoices/in_demo_active_202603',
            'period_start' => $now->subDays(55),
            'period_end' => $now->subDays(25),
            'paid_at' => $now->subDays(25),
            'meta' => ['scenario' => 'active-paid', 'plan_slug' => 'basic'],
            'created_at' => $now->subDays(25),
            'updated_at' => $now->subDays(25),
        ]);

        $this->upsertTransaction($activeUser, $activeSubscription, $activeInvoiceOne, [
            'stripe_payment_intent_id' => 'pi_demo_active_202604',
            'stripe_charge_id' => 'ch_demo_active_202604',
            'amount' => 2900,
            'currency' => 'usd',
            'type' => 'charge',
            'status' => 'succeeded',
            'description' => 'Monthly Basic renewal payment',
            'processed_at' => $now->subDays(10),
            'meta' => ['scenario' => 'active-paid'],
            'created_at' => $now->subDays(10),
            'updated_at' => $now->subDays(10),
        ]);

        $this->upsertTransaction($activeUser, $activeSubscription, $activeInvoiceTwo, [
            'stripe_payment_intent_id' => 'pi_demo_active_202603',
            'stripe_charge_id' => 'ch_demo_active_202603',
            'amount' => 2900,
            'currency' => 'usd',
            'type' => 'charge',
            'status' => 'succeeded',
            'description' => 'Previous Basic renewal payment',
            'processed_at' => $now->subDays(25),
            'meta' => ['scenario' => 'active-paid'],
            'created_at' => $now->subDays(25),
            'updated_at' => $now->subDays(25),
        ]);

        $trialUser = $this->upsertCustomer([
            'name' => 'Billing Trial Demo',
            'email' => 'billing.trial@demo.com',
            'stripe_id' => 'cus_demo_trial',
            'trial_ends_at' => $now->addDays(5),
        ]);

        $trialSubscription = $this->upsertSubscription($trialUser, [
            'stripe_id' => 'sub_demo_trial_001',
            'plan_id' => $proPlan->id,
            'stripe_status' => 'trialing',
            'stripe_price' => $proPlan->stripe_price_id ?: 'price_demo_pro_monthly',
            'quantity' => 1,
            'trial_ends_at' => $now->addDays(5),
            'created_at' => $now->subDays(9),
            'updated_at' => $now->subDay(),
        ]);

        $trialInvoice = $this->upsertInvoice($trialUser, $trialSubscription, [
            'stripe_invoice_id' => 'in_demo_trial_upcoming',
            'number' => 'INV-2026-TRIAL',
            'currency' => 'usd',
            'amount_due' => 9900,
            'amount_paid' => 0,
            'subtotal' => 9000,
            'tax' => 900,
            'status' => 'open',
            'invoice_pdf_url' => null,
            'hosted_invoice_url' => 'https://example.com/invoices/in_demo_trial_upcoming',
            'period_start' => $now,
            'period_end' => $now->addMonth(),
            'paid_at' => null,
            'meta' => ['scenario' => 'trial-open', 'plan_slug' => 'pro'],
            'created_at' => $now->subHour(),
            'updated_at' => $now->subHour(),
        ]);

        $this->upsertTransaction($trialUser, $trialSubscription, $trialInvoice, [
            'stripe_payment_intent_id' => 'pi_demo_trial_pending',
            'stripe_charge_id' => 'ch_demo_trial_pending',
            'amount' => 9900,
            'currency' => 'usd',
            'type' => 'charge',
            'status' => 'pending',
            'description' => 'Pending first Pro invoice',
            'processed_at' => $now->subHour(),
            'meta' => ['scenario' => 'trial-open'],
            'created_at' => $now->subHour(),
            'updated_at' => $now->subHour(),
        ]);

        $pastDueUser = $this->upsertCustomer([
            'name' => 'Billing Past Due Demo',
            'email' => 'billing.pastdue@demo.com',
            'stripe_id' => 'cus_demo_pastdue',
            'pm_type' => 'mastercard',
            'pm_last_four' => '4444',
        ]);

        $pastDueSubscription = $this->upsertSubscription($pastDueUser, [
            'stripe_id' => 'sub_demo_pastdue_001',
            'plan_id' => $basicPlan->id,
            'stripe_status' => 'past_due',
            'stripe_price' => $basicPlan->stripe_price_id ?: 'price_demo_basic_monthly',
            'quantity' => 1,
            'created_at' => $now->subDays(42),
            'updated_at' => $now->subDays(2),
        ]);

        $pastDueInvoice = $this->upsertInvoice($pastDueUser, $pastDueSubscription, [
            'stripe_invoice_id' => 'in_demo_pastdue_202604',
            'number' => 'INV-2026-FAIL',
            'currency' => 'usd',
            'amount_due' => 2900,
            'amount_paid' => 0,
            'subtotal' => 2500,
            'tax' => 400,
            'status' => 'failed',
            'invoice_pdf_url' => null,
            'hosted_invoice_url' => 'https://example.com/invoices/in_demo_pastdue_202604',
            'period_start' => $now->subDays(32),
            'period_end' => $now->subDays(2),
            'paid_at' => null,
            'meta' => ['scenario' => 'past-due-failed', 'attempts' => 3],
            'created_at' => $now->subDays(2),
            'updated_at' => $now->subDays(2),
        ]);

        $this->upsertTransaction($pastDueUser, $pastDueSubscription, $pastDueInvoice, [
            'stripe_payment_intent_id' => 'pi_demo_pastdue_failed',
            'stripe_charge_id' => 'ch_demo_pastdue_failed',
            'amount' => 2900,
            'currency' => 'usd',
            'type' => 'charge',
            'status' => 'failed',
            'description' => 'Charge attempt failed because the card was declined',
            'processed_at' => $now->subDays(2),
            'meta' => ['scenario' => 'past-due-failed'],
            'created_at' => $now->subDays(2),
            'updated_at' => $now->subDays(2),
        ]);

        $churnedUser = $this->upsertCustomer([
            'name' => 'Billing Churned Demo',
            'email' => 'billing.churned@demo.com',
            'stripe_id' => 'cus_demo_churned',
        ]);

        $churnedSubscription = $this->upsertSubscription($churnedUser, [
            'stripe_id' => 'sub_demo_churned_001',
            'plan_id' => $basicPlan->id,
            'stripe_status' => 'canceled',
            'stripe_price' => $basicPlan->stripe_price_id ?: 'price_demo_basic_monthly',
            'quantity' => 1,
            'ends_at' => $now->subDays(5),
            'created_at' => $now->subDays(95),
            'updated_at' => $now->subDays(5),
        ]);

        $churnedInvoice = $this->upsertInvoice($churnedUser, $churnedSubscription, [
            'stripe_invoice_id' => 'in_demo_churned_final',
            'number' => 'INV-2026-CANCEL',
            'currency' => 'usd',
            'amount_due' => 2900,
            'amount_paid' => 2900,
            'subtotal' => 2500,
            'tax' => 400,
            'status' => 'paid',
            'invoice_pdf_url' => 'https://example.com/invoices/in_demo_churned_final.pdf',
            'hosted_invoice_url' => 'https://example.com/invoices/in_demo_churned_final',
            'period_start' => $now->subDays(65),
            'period_end' => $now->subDays(35),
            'paid_at' => $now->subDays(35),
            'meta' => ['scenario' => 'canceled-final-paid', 'plan_slug' => 'basic'],
            'created_at' => $now->subDays(35),
            'updated_at' => $now->subDays(35),
        ]);

        $this->upsertTransaction($churnedUser, $churnedSubscription, $churnedInvoice, [
            'stripe_payment_intent_id' => 'pi_demo_churned_refund',
            'stripe_charge_id' => 'ch_demo_churned_refund',
            'amount' => 1450,
            'currency' => 'usd',
            'type' => 'refund',
            'status' => 'succeeded',
            'description' => 'Partial refund after cancellation',
            'processed_at' => $now->subDays(4),
            'meta' => ['scenario' => 'canceled-refund'],
            'created_at' => $now->subDays(4),
            'updated_at' => $now->subDays(4),
        ]);

        $unmappedUser = $this->upsertCustomer([
            'name' => 'Billing Unmapped Demo',
            'email' => 'billing.unmapped@demo.com',
            'stripe_id' => 'cus_demo_unmapped',
            'pm_type' => 'visa',
            'pm_last_four' => '1111',
        ]);

        $unmappedSubscription = $this->upsertSubscription($unmappedUser, [
            'stripe_id' => 'sub_demo_unmapped_001',
            'plan_id' => null,
            'stripe_status' => 'active',
            'stripe_price' => 'price_demo_enterprise_unmapped',
            'quantity' => 3,
            'created_at' => $now->subDays(18),
            'updated_at' => $now->subDays(3),
        ]);

        $unmappedInvoice = $this->upsertInvoice($unmappedUser, $unmappedSubscription, [
            'stripe_invoice_id' => 'in_demo_unmapped_202604',
            'number' => 'INV-2026-UNMAPPED',
            'currency' => 'usd',
            'amount_due' => 14900,
            'amount_paid' => 14900,
            'subtotal' => 14000,
            'tax' => 900,
            'status' => 'paid',
            'invoice_pdf_url' => 'https://example.com/invoices/in_demo_unmapped_202604.pdf',
            'hosted_invoice_url' => 'https://example.com/invoices/in_demo_unmapped_202604',
            'period_start' => $now->subDays(33),
            'period_end' => $now->subDays(3),
            'paid_at' => $now->subDays(3),
            'meta' => ['scenario' => 'unmapped-active'],
            'created_at' => $now->subDays(3),
            'updated_at' => $now->subDays(3),
        ]);

        $this->upsertTransaction($unmappedUser, $unmappedSubscription, $unmappedInvoice, [
            'stripe_payment_intent_id' => 'pi_demo_unmapped_charge',
            'stripe_charge_id' => 'ch_demo_unmapped_charge',
            'amount' => 14900,
            'currency' => 'usd',
            'type' => 'charge',
            'status' => 'succeeded',
            'description' => 'Enterprise charge without local plan mapping',
            'processed_at' => $now->subDays(3),
            'meta' => ['scenario' => 'unmapped-active'],
            'created_at' => $now->subDays(3),
            'updated_at' => $now->subDays(3),
        ]);
    }

    private function upsertCustomer(array $attributes): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $attributes['email']],
            [
                'name' => $attributes['name'],
                'password' => Hash::make('demo'),
                'email_verified_at' => now(),
                'role' => 'customer',
                'stripe_id' => $attributes['stripe_id'] ?? null,
                'pm_type' => $attributes['pm_type'] ?? null,
                'pm_last_four' => $attributes['pm_last_four'] ?? null,
                'trial_ends_at' => $attributes['trial_ends_at'] ?? null,
            ],
        );

        $user->syncRoles(['customer']);

        return $user;
    }

    private function upsertSubscription(User $user, array $attributes): Subscription
    {
        $subscription = Subscription::query()->updateOrCreate(
            ['stripe_id' => $attributes['stripe_id']],
            [
                'user_id' => $user->id,
                'plan_id' => $attributes['plan_id'] ?? null,
                'type' => $attributes['type'] ?? 'default',
                'stripe_status' => $attributes['stripe_status'],
                'stripe_price' => $attributes['stripe_price'] ?? null,
                'quantity' => $attributes['quantity'] ?? 1,
                'trial_ends_at' => $attributes['trial_ends_at'] ?? null,
                'ends_at' => $attributes['ends_at'] ?? null,
            ],
        );

        $this->syncTimestamps(
            $subscription,
            $attributes['created_at'] ?? now(),
            $attributes['updated_at'] ?? ($attributes['created_at'] ?? now()),
        );

        return $subscription;
    }

    private function upsertInvoice(User $user, ?Subscription $subscription, array $attributes): BillingInvoice
    {
        $invoice = BillingInvoice::query()->updateOrCreate(
            ['stripe_invoice_id' => $attributes['stripe_invoice_id']],
            [
                'user_id' => $user->id,
                'subscription_id' => $subscription?->id,
                'number' => $attributes['number'] ?? null,
                'currency' => $attributes['currency'] ?? 'usd',
                'amount_due' => $attributes['amount_due'] ?? 0,
                'amount_paid' => $attributes['amount_paid'] ?? 0,
                'subtotal' => $attributes['subtotal'] ?? null,
                'tax' => $attributes['tax'] ?? null,
                'status' => $attributes['status'] ?? 'draft',
                'invoice_pdf_url' => $attributes['invoice_pdf_url'] ?? null,
                'hosted_invoice_url' => $attributes['hosted_invoice_url'] ?? null,
                'period_start' => $attributes['period_start'] ?? null,
                'period_end' => $attributes['period_end'] ?? null,
                'paid_at' => $attributes['paid_at'] ?? null,
                'meta' => $attributes['meta'] ?? null,
            ],
        );

        $this->syncTimestamps(
            $invoice,
            $attributes['created_at'] ?? now(),
            $attributes['updated_at'] ?? ($attributes['created_at'] ?? now()),
        );

        return $invoice;
    }

    private function upsertTransaction(
        User $user,
        ?Subscription $subscription,
        ?BillingInvoice $invoice,
        array $attributes,
    ): BillingTransaction {
        $transaction = BillingTransaction::query()->updateOrCreate(
            ['stripe_payment_intent_id' => $attributes['stripe_payment_intent_id']],
            [
                'user_id' => $user->id,
                'invoice_id' => $invoice?->id,
                'subscription_id' => $subscription?->id,
                'stripe_charge_id' => $attributes['stripe_charge_id'] ?? null,
                'amount' => $attributes['amount'] ?? 0,
                'currency' => $attributes['currency'] ?? 'usd',
                'type' => $attributes['type'] ?? 'charge',
                'status' => $attributes['status'] ?? 'pending',
                'description' => $attributes['description'] ?? null,
                'processed_at' => $attributes['processed_at'] ?? null,
                'meta' => $attributes['meta'] ?? null,
            ],
        );

        $this->syncTimestamps(
            $transaction,
            $attributes['created_at'] ?? now(),
            $attributes['updated_at'] ?? ($attributes['created_at'] ?? now()),
        );

        return $transaction;
    }

    private function syncTimestamps(Model $model, mixed $createdAt, mixed $updatedAt): void
    {
        $usesTimestamps = $model->timestamps;

        $model->timestamps = false;
        $model->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ])->saveQuietly();
        $model->timestamps = $usesTimestamps;
    }
}

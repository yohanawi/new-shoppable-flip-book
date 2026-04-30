<x-default-layout>

    @section('title')
        Billing Plans
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.plans') }}
    @endsection

    @php
        $hasUnseededPlans = $plans->contains(fn($plan) => !$plan->exists || !$plan->getKey());
    @endphp

    @include('pages.apps.billing.partials._alerts')
    @include('pages.apps.billing.partials._subnav')
    @include('pages.apps.billing.partials._summary-cards')

    @if ($hasUnseededPlans)
        <div class="alert alert-warning mb-8">
            Billing plans have not been seeded yet. Run <code>php artisan db:seed --class=BillingSeeder</code> to enable
            plan changes.
        </div>
    @endif

    <div class="card mb-8">
        <div class="card-body d-flex flex-column flex-xl-row justify-content-between gap-6 items-center">
            <div>
                <h3 class="mt-2">Plans and Subscription</h3>
            </div>
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <a href="{{ route('billing.payments.create') }}" class="btn btn-light-primary">Submit Manual Payment</a>
                @if ($stripeConfigured)
                    <a href="{{ route('billing.payment-methods.index') }}" class="btn btn-light">Manage Payment
                        Methods</a>
                @endif
                @if ($subscription && !$subscription->onGracePeriod())
                    <form method="POST" action="{{ route('billing.subscription.cancel') }}">
                        @csrf
                        <button type="submit" class="btn btn-light-danger">Cancel Subscription</button>
                    </form>
                @elseif ($subscription && $subscription->onGracePeriod())
                    <form method="POST" action="{{ route('billing.subscription.resume') }}">
                        @csrf
                        <button type="submit" class="btn btn-light-success">Resume Subscription</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @include('pages.apps.billing.partials._plans-grid')

</x-default-layout>

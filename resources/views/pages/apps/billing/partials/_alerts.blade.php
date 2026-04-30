@php
    $billingCheckoutState = $checkoutState ?? request()->query('checkout');
@endphp

@if ($billingCheckoutState === 'success')
    <div class="alert alert-success mb-8">Stripe checkout completed. Your subscription details will refresh as soon as
        the payment is confirmed.</div>
@elseif ($billingCheckoutState === 'failed')
    <div class="alert alert-danger mb-8">Payment could not be activated automatically. Please review the billing details
        below or contact support.</div>
@elseif ($billingCheckoutState === 'cancelled')
    <div class="alert alert-warning mb-8">Payment was not completed because Stripe checkout was cancelled.</div>
@endif

@if (session('success'))
    <div class="alert alert-success mb-8">{{ session('success') }}</div>
@endif

@if (session('status'))
    <div class="alert alert-info mb-8">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger mb-8">
        <div class="fw-bold mb-2">Billing action could not be completed.</div>
        <ul class="mb-0 ps-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $billingActiveSection = $billingActiveSection ?? 'overview';
    $billingNavigation = [
        ['key' => 'overview', 'label' => 'Overview', 'route' => route('billing.index')],
        ['key' => 'plans', 'label' => 'Plans', 'route' => route('billing.plans')],
        ['key' => 'payment-methods', 'label' => 'Payment Methods', 'route' => route('billing.payment-methods.index')],
        ['key' => 'invoices', 'label' => 'Invoices and Activity', 'route' => route('billing.invoices.index')],
        ['key' => 'payment-requests', 'label' => 'Payment Requests', 'route' => route('billing.payments.history')],
    ];
@endphp

<div class="card mb-8">
    <div class="card-body py-5">
        <ul
            class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold flex-nowrap">
            @foreach ($billingNavigation as $billingItem)
                <li class="nav-item">
                    <a href="{{ $billingItem['route'] }}"
                        class="nav-link text-active-primary ms-0 me-6 {{ $billingActiveSection === $billingItem['key'] ? 'active' : '' }}">
                        {{ $billingItem['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
 
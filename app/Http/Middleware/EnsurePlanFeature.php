<?php

namespace App\Http\Middleware;

use App\Services\BillingManager;
use Closure;
use Illuminate\Http\Request;

class EnsurePlanFeature
{
    public function __construct(private readonly BillingManager $billingManager) {}

    public function handle(Request $request, Closure $next, string $feature)
    {
        $user = $request->user();

        abort_unless($user, 403);

        if (!$this->billingManager->hasFeature($user, $feature)) {
            abort(403, 'Your current billing plan does not include this feature.');
        }

        return $next($request);
    }
}

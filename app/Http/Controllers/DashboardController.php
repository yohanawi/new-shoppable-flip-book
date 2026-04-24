<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboardService)
    {
        $user = $request->user();

        abort_unless($user, 403);
        abort_unless(
            ($user->isAdmin() && $user->can('admin.dashboard.view'))
                || ($user->isCustomer() && $user->can('customer.dashboard.view')),
            403
        );

        addVendors(['amcharts', 'amcharts-maps', 'amcharts-stock']);

        return view('pages/dashboards.index', $dashboardService->buildFor($user));
    }
}

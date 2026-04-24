<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreSupportTicketCategoryRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SupportTicketCategoryController extends Controller
{
    public function index()
    {
        abort_unless(request()->user()?->isAdmin(), 403);

        $categories = SupportTicketCategory::query()
            ->withCount('tickets')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages.apps.tickets.categories.index', compact('categories'));
    }

    public function store(StoreSupportTicketCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['sort_order'] = (int) SupportTicketCategory::query()->max('sort_order') + 1;

        SupportTicketCategory::query()->create($validated);

        return back()->with('success', 'Ticket category created successfully.');
    }

    public function update(StoreSupportTicketCategoryRequest $request, SupportTicketCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return back()->with('success', 'Ticket category updated successfully.');
    }

    public function destroy(SupportTicketCategory $category): RedirectResponse
    {
        abort_unless(request()->user()?->isAdmin(), 403);

        DB::transaction(function () use ($category) {
            SupportTicket::query()
                ->where('support_ticket_category_id', $category->id)
                ->update([
                    'category' => $category->slug,
                    'support_ticket_category_id' => null,
                ]);

            $category->delete();
        });

        return back()->with('success', 'Ticket category deleted successfully.');
    }
}

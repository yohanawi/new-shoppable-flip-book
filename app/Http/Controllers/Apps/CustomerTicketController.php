<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreSupportTicketFeedbackRequest;
use App\Http\Requests\Tickets\StoreSupportTicketReplyRequest;
use App\Http\Requests\Tickets\StoreSupportTicketRequest;
use App\Http\Requests\Tickets\UpdateSupportTicketStatusRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerTicketController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', SupportTicket::class);

        $user = $this->currentUser();
        $isAdminView = $user?->isAdmin() ?? false;
        $filters = [
            'search' => trim((string) $request->string('search')),
            'status' => (string) $request->string('status'),
            'priority' => (string) $request->string('priority'),
            'category' => (string) $request->string('category'),
        ];

        $tickets = SupportTicket::query()
            ->with(['user', 'categoryRelation'])
            ->withCount('messages')
            ->when(!$isAdminView, fn($query) => $query->where('user_id', Auth::id()))
            ->when(filled($filters['search']), function (Builder $query) use ($filters, $isAdminView) {
                $search = $filters['search'];
                $like = '%' . $search . '%';

                $query->where(function (Builder $nestedQuery) use ($search, $like, $isAdminView) {
                    if (ctype_digit($search)) {
                        $nestedQuery->orWhere('support_tickets.id', (int) $search);
                    }

                    $nestedQuery->orWhere('support_tickets.subject', 'like', $like);

                    if ($isAdminView) {
                        $nestedQuery->orWhereHas('user', function (Builder $userQuery) use ($like) {
                            $userQuery
                                ->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                    }
                });
            })
            ->when(filled($filters['status']), fn(Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['priority']), fn(Builder $query) => $query->where('priority', $filters['priority']))
            ->when(filled($filters['category']), function (Builder $query) use ($filters) {
                $categoryFilter = $filters['category'];

                if (ctype_digit($categoryFilter)) {
                    $query->where('support_ticket_category_id', (int) $categoryFilter);

                    return;
                }

                $normalizedCategory = $this->normalizeCategorySlug($categoryFilter);

                $query->where(function (Builder $nestedQuery) use ($normalizedCategory) {
                    $nestedQuery
                        ->where('category', $normalizedCategory)
                        ->orWhereHas('categoryRelation', function (Builder $categoryQuery) use ($normalizedCategory) {
                            $categoryQuery->where('slug', $normalizedCategory);
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = SupportTicketCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('pages.apps.tickets.index', compact('tickets', 'isAdminView', 'categories', 'filters'));
    }

    public function create()
    {
        $this->authorize('create', SupportTicket::class);

        $categories = SupportTicketCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('pages.apps.tickets.create', compact('categories'));
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $category = $this->resolveCategory($validated);

        if (!$category) {
            throw ValidationException::withMessages([
                'category_id' => 'Please select a valid support category.',
            ]);
        }

        $attachment = $this->storeAttachment($request);

        DB::transaction(function () use ($validated, $category, $attachment) {
            $ticket = SupportTicket::query()->create([
                'user_id' => Auth::id(),
                'support_ticket_category_id' => $category->id,
                'subject' => $validated['subject'],
                'category' => $category->slug,
                'priority' => $validated['priority'] ?? 'medium',
                'message' => $validated['message'],
                'status' => 'open',
                'attachment_path' => $attachment['path'],
                'attachment_name' => $attachment['name'],
            ]);

            SupportTicketMessage::query()->create([
                'support_ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'is_admin' => false,
                'message' => $validated['message'],
            ]);
        });

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Support ticket created successfully!');
    }


    public function show(SupportTicket $ticket)
    {
        $this->authorize('view', $ticket);

        $ticket->loadMissing(['user', 'messages.user', 'categoryRelation']);

        $messages = $this->buildTicketMessages($ticket);

        $isAdminView = $this->currentUser()?->isAdmin() ?? false;
        $statusOptions = [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'closed' => 'Closed',
        ];

        return view('pages.apps.tickets.show', compact('ticket', 'messages', 'isAdminView', 'statusOptions'));
    }

    public function reply(StoreSupportTicketReplyRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $isAdminReply = $this->currentUser()?->isAdmin() ?? false;

        SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'is_admin' => $isAdminReply,
            'message' => $request->validated('message'),
        ]);

        $ticket->update(['status' => $isAdminReply ? 'in_progress' : 'open']);

        return back()->with('success', 'Reply sent successfully.');
    }

    public function updateStatus(UpdateSupportTicketStatusRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $status = $request->validated('status');

        $ticket->update([
            'status' => $status,
            'closed_at' => $status === 'closed' ? ($ticket->closed_at ?? now()) : null,
        ]);

        return back()->with('success', 'Ticket status updated successfully.');
    }

    public function storeFeedback(StoreSupportTicketFeedbackRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $ticket->update([
            'feedback_rating' => (int) $request->validated('feedback_rating'),
            'feedback_comment' => $request->validated('feedback_comment'),
        ]);

        return back()->with('success', 'Thank you for your feedback.');
    }

    private function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private function buildTicketMessages(SupportTicket $ticket): Collection
    {
        $messages = $ticket->messages
            ->sortBy('created_at')
            ->values();

        $hasInitialMessage = $messages->contains(function (SupportTicketMessage $message) use ($ticket) {
            return !$message->is_admin
                && (int) $message->user_id === (int) $ticket->user_id
                && trim((string) $message->message) === trim((string) $ticket->message);
        });

        if (!$hasInitialMessage && filled($ticket->message)) {
            $initialMessage = new SupportTicketMessage([
                'support_ticket_id' => $ticket->id,
                'user_id' => $ticket->user_id,
                'is_admin' => false,
                'message' => $ticket->message,
            ]);
            $initialMessage->setRelation('user', $ticket->user);
            $initialMessage->created_at = $ticket->created_at;
            $initialMessage->updated_at = $ticket->updated_at;

            $messages->prepend($initialMessage);
        }

        return $messages->values();
    }

    private function resolveCategory(array $validated): ?SupportTicketCategory
    {
        if (!empty($validated['category_id'])) {
            return SupportTicketCategory::query()->find($validated['category_id']);
        }

        $category = trim((string) ($validated['category'] ?? ''));

        if ($category === '') {
            return null;
        }

        $slug = $this->normalizeCategorySlug($category);

        return SupportTicketCategory::query()
            ->where('slug', $slug)
            ->orWhereRaw('LOWER(name) = ?', [Str::lower($category)])
            ->first();
    }

    private function normalizeCategorySlug(string $category): string
    {
        $normalized = Str::slug($category);

        return match ($normalized) {
            'billing' => 'payment',
            default => $normalized,
        };
    }

    private function storeAttachment(StoreSupportTicketRequest $request): array
    {
        if (!$request->hasFile('attachment')) {
            return ['path' => null, 'name' => null];
        }

        $file = $request->file('attachment');

        return [
            'path' => $file->store('support-tickets/attachments', 'public'),
            'name' => $file->getClientOriginalName(),
        ];
    }
}

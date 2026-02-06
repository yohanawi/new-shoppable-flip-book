<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerTicketController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('pages.apps.tickets.index', compact('tickets'));
    }

    public function create()
    {
        return view('pages.apps.tickets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|in:technical,billing,general',
            'priority' => 'required|in:low,medium,high',
            'message' => 'required|string',
        ]);

        SupportTicket::create([
            'user_id' => Auth::id(),
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'message' => $validated['message'],
            'status' => 'open',
        ]);

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Support ticket created successfully!');
    }


    public function show(SupportTicket $ticket)
    {
        abort_if($ticket->user_id !== Auth::id(), 403);

        return view('pages.apps.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        abort_if($ticket->user_id !== Auth::id(), 403);

        $request->validate([
            'message' => 'required|string',
        ]);

        SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'is_admin' => false,
            'message' => $request->message,
        ]);

        $ticket->update(['status' => 'open']);

        return back()->with('success', 'Reply sent successfully.');
    }
}

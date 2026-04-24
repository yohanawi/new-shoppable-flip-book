<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (!$user->isAdmin()) {
            return null;
        }

        if ($ability === 'leaveFeedback') {
            return false;
        }

        if ($ability === 'reply') {
            return null;
        }

        return true;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('tickets.view') || $user->isCustomer();
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return (int) $ticket->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('tickets.create') || $user->isCustomer();
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        if ($ticket->status === 'closed') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return (int) $ticket->user_id === (int) $user->id
            && ($user->can('tickets.reply') || $user->isCustomer());
    }

    public function updateStatus(User $user, SupportTicket $ticket): bool
    {
        return false;
    }

    public function leaveFeedback(User $user, SupportTicket $ticket): bool
    {
        return (int) $ticket->user_id === (int) $user->id
            && $ticket->status === 'closed'
            && $ticket->feedback_rating === null;
    }
}

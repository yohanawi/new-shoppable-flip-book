<?php

namespace App\Http\Requests\Tickets;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof SupportTicket
            ? ($this->user()?->can('leaveFeedback', $ticket) ?? false)
            : false;
    }

    public function rules(): array
    {
        return [
            'feedback_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback_comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

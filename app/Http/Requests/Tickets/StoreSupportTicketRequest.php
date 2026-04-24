<?php

namespace App\Http\Requests\Tickets;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SupportTicket::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('support_ticket_categories', 'id')->where('is_active', true),
            ],
            'category' => ['nullable', 'string', 'max:100'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,txt,xlsx,csv'],
        ];
    }
}

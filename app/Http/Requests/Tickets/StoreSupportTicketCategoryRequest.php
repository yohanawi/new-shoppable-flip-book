<?php

namespace App\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreSupportTicketCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = is_object($category) && method_exists($category, 'getKey')
            ? $category->getKey()
            : null;

        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('support_ticket_categories', 'slug')->ignore($categoryId),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug') ?: $this->input('name');

        $this->merge([
            'slug' => filled($slug) ? Str::slug((string) $slug) : null,
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}

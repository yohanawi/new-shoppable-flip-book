<x-default-layout>

    @section('title')
        Support Ticket #{{ $ticket->id }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('tickets.show', $ticket) }}
    @endsection

    @php
        $statusConfig = [
            'open' => ['color' => 'primary', 'icon' => 'ki-timer'],
            'in_progress' => ['color' => 'warning', 'icon' => 'ki-abstract-26'],
            'closed' => ['color' => 'success', 'icon' => 'ki-check-circle'],
        ];
        $config = $statusConfig[$ticket->status] ?? ['color' => 'primary', 'icon' => 'ki-timer'];
    @endphp

    <div id="kt_app_content_container" class="app-container container-xxl">
        <div class="row g-5 g-xl-8">
            <div class="col-xl-8">
                <div class="card mb-5">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-5">
                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="symbol symbol-circle symbol-50px me-3">
                                    <span class="symbol-label bg-light-primary text-primary fs-1 fw-bold">
                                        <i class="ki-duotone ki-message-text-2 fs-2x">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="text-gray-900 fw-bold fs-4">{{ $ticket->subject }}</span>
                                    <span class="text-muted fw-semibold d-block fs-7">
                                        Ticket #{{ $ticket->id }} • Created {{ $ticket->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-light-primary">
                                <i class="ki-duotone ki-arrow-left fs-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                Back
                            </a>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge badge-light-info px-4 py-2">{{ $ticket->category_name }}</span>
                            <span class="badge badge-light-warning px-4 py-2">{{ ucfirst($ticket->priority) }}
                                Priority</span>
                            <span class="badge badge-light-{{ $config['color'] }} px-4 py-2">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </span>
                            @if ($ticket->attachment_name)
                                <a href="{{ $ticket->attachment_url }}" target="_blank"
                                    class="badge badge-light-primary px-4 py-2">
                                    Attachment: {{ $ticket->attachment_name }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card card-flush mb-5">
                    <div class="card-header pt-7" id="kt_chat_messenger_header">
                        <div class="card-title">
                            <h2>Conversation</h2>
                        </div>
                    </div>

                    <div class="card-body" id="kt_chat_messenger_body">
                        <div class="scroll-y me-n5 pe-5" data-kt-element="messages" data-kt-scroll="true"
                            data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_header, #kt_app_header, #kt_app_toolbar, #kt_toolbar, #kt_footer, #kt_app_footer, #kt_chat_messenger_header, #kt_chat_messenger_footer"
                            data-kt-scroll-wrappers="#kt_content, #kt_app_content, #kt_chat_messenger_body"
                            data-kt-scroll-offset="5px" style="max-height: 600px;">
                            @forelse ($messages as $message)
                                @if (!$message->is_admin)
                                    <div class="d-flex justify-content-start mb-10">
                                        <div class="d-flex flex-column align-items-start">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="symbol symbol-35px symbol-circle me-3">
                                                    <span
                                                        class="symbol-label bg-light-primary text-primary fw-bold fs-6">
                                                        {{ substr($message->user->name ?? 'U', 0, 1) }}
                                                    </span>
                                                </div>
                                                <div class="ms-3">
                                                    <span class="fs-5 fw-bold text-gray-900 me-1">
                                                        {{ $message->user->name ?? ($ticket->user?->name ?? 'Customer') }}
                                                    </span>
                                                    <span class="text-muted fs-7 mb-1">
                                                        {{ $message->created_at->format('M d, H:i') }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div
                                                class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-400px text-start">
                                                {{ $message->message }}
                                            </div>
                                            <span
                                                class="text-muted fs-8 mt-1">{{ $message->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="d-flex justify-content-end mb-10">
                                        <div class="d-flex flex-column align-items-end">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="me-3 text-end">
                                                    <span
                                                        class="text-muted fs-7 mb-1">{{ $message->created_at->format('M d, H:i') }}</span>
                                                    <div class="fs-5 fw-bold text-gray-900">Support Team</div>
                                                </div>
                                                <div class="symbol symbol-35px symbol-circle ms-3">
                                                    <span class="symbol-label bg-light-success text-success">
                                                        <i class="ki-duotone ki-shield-tick fs-2">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </span>
                                                </div>
                                            </div>
                                            <div
                                                class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end">
                                                {{ $message->message }}
                                            </div>
                                            <span
                                                class="text-muted fs-8 mt-1">{{ $message->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <div class="text-center py-15">
                                    <div class="mb-7">
                                        <i class="ki-duotone ki-message-text-2 fs-5x text-gray-400">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </div>
                                    <h3 class="text-gray-800 fw-bold mb-2">No Messages Yet</h3>
                                    <p class="text-gray-500 fs-6 fw-semibold mb-0">Start the conversation below.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @if ($ticket->status !== 'closed')
                        <div class="card-footer pt-4" id="kt_chat_messenger_footer">
                            <form method="POST" action="{{ route('tickets.reply', $ticket) }}">
                                @csrf
                                <textarea name="message" class="form-control form-control-flush mb-3 @error('message') is-invalid @enderror"
                                    rows="3" placeholder="Type your message here..." required>{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
                                @enderror

                                <div class="d-flex flex-stack">
                                    <div class="text-muted fs-7">
                                        {{ $isAdminView ? 'Admin replies move the ticket to In Progress.' : 'Your reply will keep the conversation active.' }}
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        Send
                                        <i class="ki-duotone ki-send fs-2 ms-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="card-footer pt-4">
                            <div class="alert alert-dismissible bg-light-danger d-flex align-items-center p-5">
                                <i class="ki-duotone ki-shield-cross fs-2hx text-danger me-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                <div class="d-flex flex-column">
                                    <h4 class="mb-1 text-danger">Ticket Closed</h4>
                                    <span class="text-gray-700">This ticket has been closed and cannot receive new
                                        replies.</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card card-flush mb-5">
                    <div class="card-header pt-7">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-800">Ticket Information</span>
                            <span class="text-gray-500 mt-1 fw-semibold fs-6">Current status, context, and follow-up
                                actions.</span>
                        </h3>
                    </div>

                    <div class="card-body pt-6">
                        <div class="d-flex flex-stack mb-5">
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Status</div>
                            <div class="badge badge-light-{{ $config['color'] }} fs-7 fw-bold">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </div>
                        </div>

                        <div class="separator separator-dashed mb-5"></div>

                        @if ($isAdminView)
                            <div class="d-flex flex-stack mb-5">
                                <div class="text-gray-700 fw-semibold fs-6 me-2">Customer</div>
                                <div class="text-end">
                                    <div class="text-gray-800 fw-bold fs-6">
                                        {{ $ticket->user?->name ?? 'Unknown customer' }}</div>
                                    <div class="text-muted fs-7">{{ $ticket->user?->email }}</div>
                                </div>
                            </div>

                            <div class="separator separator-dashed mb-5"></div>
                        @endif

                        <div class="d-flex flex-stack mb-5">
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Category</div>
                            <div class="text-gray-800 fw-bold fs-6">{{ $ticket->category_name }}</div>
                        </div>

                        <div class="separator separator-dashed mb-5"></div>

                        <div class="d-flex flex-stack mb-5">
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Priority</div>
                            <div class="text-gray-800 fw-bold fs-6">{{ ucfirst($ticket->priority) }}</div>
                        </div>

                        <div class="separator separator-dashed mb-5"></div>

                        <div class="d-flex flex-stack mb-5">
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Created</div>
                            <div class="text-gray-800 fw-bold fs-6">{{ $ticket->created_at->format('M d, Y') }}</div>
                        </div>

                        <div class="separator separator-dashed mb-5"></div>

                        <div class="d-flex flex-stack mb-5">
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Last Updated</div>
                            <div class="text-gray-800 fw-bold fs-6">{{ $ticket->updated_at->diffForHumans() }}</div>
                        </div>

                        @if ($ticket->attachment_name)
                            <div class="separator separator-dashed mb-5"></div>

                            <div class="d-flex flex-stack mb-5">
                                <div class="text-gray-700 fw-semibold fs-6 me-2">Attachment</div>
                                <a href="{{ $ticket->attachment_url }}" target="_blank"
                                    class="btn btn-sm btn-light-primary">
                                    Open
                                </a>
                            </div>
                        @endif

                        @if ($isAdminView)
                            <div class="separator separator-dashed mb-5"></div>

                            <form method="POST" action="{{ route('tickets.status.update', $ticket) }}">
                                @csrf
                                @method('PATCH')
                                <label class="form-label fw-semibold">Update Status</label>
                                <div class="d-flex gap-3">
                                    <select name="status" class="form-select form-select-solid"
                                        data-control="select2" data-hide-search="true">
                                        @foreach ($statusOptions as $statusValue => $statusLabel)
                                            <option value="{{ $statusValue }}"
                                                {{ $ticket->status === $statusValue ? 'selected' : '' }}>
                                                {{ $statusLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-light-primary">Save</button>
                                </div>
                            </form>
                        @endif

                        <div class="separator separator-dashed my-5"></div>

                        <div class="d-flex flex-stack">
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Total Messages</div>
                            <div class="text-gray-800 fw-bold fs-6">{{ $messages->count() }}</div>
                        </div>

                        @if ($ticket->feedback_rating)
                            <div class="separator separator-dashed my-5"></div>

                            <div>
                                <div class="text-gray-700 fw-semibold fs-6 mb-2">Customer Feedback</div>
                                <div class="text-warning fs-3 mb-2">
                                    {{ str_repeat('★', (int) $ticket->feedback_rating) }}</div>
                                <div class="text-gray-800 fw-bold fs-6">{{ $ticket->feedback_rating }}/5</div>
                                @if ($ticket->feedback_comment)
                                    <div class="text-muted fs-7 mt-2">{{ $ticket->feedback_comment }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                @if (!$isAdminView && $ticket->status === 'closed' && !$ticket->feedback_rating)
                    <div class="card card-flush mb-5">
                        <div class="card-header pt-7">
                            <div class="card-title d-flex flex-column">
                                <span class="fw-bold fs-3">Rate This Ticket</span>
                                <span class="text-muted fs-7">Closed tickets can receive one rating and optional
                                    comment.</span>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <form method="POST" action="{{ route('tickets.feedback.store', $ticket) }}">
                                @csrf
                                <div class="mb-6">
                                    <label class="form-label required fw-semibold">Rating</label>
                                    <select name="feedback_rating"
                                        class="form-select form-select-solid @error('feedback_rating') is-invalid @enderror"
                                        data-control="select2" data-hide-search="true" required>
                                        <option value="">Select rating</option>
                                        @for ($rating = 5; $rating >= 1; $rating--)
                                            <option value="{{ $rating }}"
                                                {{ (int) old('feedback_rating') === $rating ? 'selected' : '' }}>
                                                {{ $rating }} / 5
                                            </option>
                                        @endfor
                                    </select>
                                    @error('feedback_rating')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-6">
                                    <label class="form-label fw-semibold">Comment</label>
                                    <textarea name="feedback_comment" rows="4"
                                        class="form-control form-control-solid @error('feedback_comment') is-invalid @enderror"
                                        placeholder="Tell us how the support experience went.">{{ old('feedback_comment') }}</textarea>
                                    @error('feedback_comment')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Feedback</button>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="card bg-light-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-5">
                            <i class="ki-duotone ki-information-5 fs-3x text-info me-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            <div>
                                <h3 class="text-info mb-1">Need More Help?</h3>
                                <p class="text-gray-700 fw-semibold fs-6 mb-0">
                                    Keep all replies in this thread so the full ticket history stays together.
                                </p>
                            </div>
                        </div>

                        <div class="separator separator-dashed border-info mb-5"></div>

                        <div class="mb-4">
                            <div class="d-flex align-items-center">
                                <i class="ki-duotone ki-message-text-2 fs-2 text-info me-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                <a href="{{ route('tickets.index') }}"
                                    class="text-info fw-bold text-hover-primary">View All Tickets</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-default-layout>

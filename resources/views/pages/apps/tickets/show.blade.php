<x-default-layout>

    @section('title')
        Support Ticket #{{ $ticket->id }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('tickets.show', $ticket) }}
    @endsection

    <div id="kt_app_content_container" class="app-container container-xxl">

        <div class="row g-5 g-xl-8">
            {{-- Main Chat Area --}}
            <div class="col-xl-8">
                {{-- Ticket Header --}}
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
                                    <a href="#" class="text-gray-900 fw-bold text-hover-primary fs-4">
                                        {{ $ticket->subject }}
                                    </a>
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

                        {{-- Status Pills --}}
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge badge-light-info px-4 py-2">
                                <i class="ki-duotone ki-category fs-5 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ ucfirst($ticket->category) }}
                            </span>

                            <span class="badge badge-light-warning px-4 py-2">
                                <i class="ki-duotone ki-notification-status fs-5 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                                {{ ucfirst($ticket->priority) }} Priority
                            </span>

                            @php
                                $statusConfig = [
                                    'open' => ['color' => 'primary', 'icon' => 'ki-timer'],
                                    'in_progress' => ['color' => 'success', 'icon' => 'ki-abstract-26'],
                                    'closed' => ['color' => 'danger', 'icon' => 'ki-cross-circle'],
                                ];
                                $config = $statusConfig[$ticket->status] ?? [
                                    'color' => 'primary',
                                    'icon' => 'ki-timer',
                                ];
                            @endphp

                            <span class="badge badge-light-{{ $config['color'] }} px-4 py-2">
                                <i class="ki-duotone {{ $config['icon'] }} fs-5 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Chat Messages --}}
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

                            @forelse ($ticket->messages as $message)
                                @if (!$message->is_admin)
                                    {{-- Customer Message --}}
                                    <div class="d-flex justify-content-start mb-10">
                                        <div class="d-flex flex-column align-items-start">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="symbol symbol-35px symbol-circle me-3">
                                                    <span
                                                        class="symbol-label bg-light-primary text-primary fw-bold fs-6">
                                                        {{ substr($message->user->name, 0, 1) }}
                                                    </span>
                                                </div>
                                                <div class="ms-3">
                                                    <a href="#"
                                                        class="fs-5 fw-bold text-gray-900 text-hover-primary me-1">
                                                        {{ $message->user->name }}
                                                    </a>
                                                    <span class="text-muted fs-7 mb-1">
                                                        {{ $message->created_at->format('M d, H:i') }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-400px text-start"
                                                data-kt-element="message-text">
                                                {{ $message->message }}
                                            </div>
                                            <span class="text-muted fs-8 mt-1">
                                                {{ $message->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    {{-- Support Team Message --}}
                                    <div class="d-flex justify-content-end mb-10">
                                        <div class="d-flex flex-column align-items-end">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="me-3">
                                                    <span class="text-muted fs-7 mb-1">
                                                        {{ $message->created_at->format('M d, H:i') }}
                                                    </span>
                                                    <a href="#"
                                                        class="fs-5 fw-bold text-gray-900 text-hover-primary ms-1">
                                                        Support Team
                                                    </a>
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
                                            <div class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end"
                                                data-kt-element="message-text">
                                                {{ $message->message }}
                                            </div>
                                            <span class="text-muted fs-8 mt-1">
                                                {{ $message->created_at->diffForHumans() }}
                                            </span>
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
                                    <p class="text-gray-500 fs-6 fw-semibold mb-0">
                                        Start the conversation by sending your first message below
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Reply Footer --}}
                    @if ($ticket->status !== 'closed')
                        <div class="card-footer pt-4" id="kt_chat_messenger_footer">
                            <form method="POST" action="{{ route('tickets.reply', $ticket) }}">
                                @csrf
                                <textarea name="message" class="form-control form-control-flush mb-3" rows="3"
                                    placeholder="Type your message here..." required></textarea>

                                <div class="d-flex flex-stack">
                                    <div class="d-flex align-items-center me-2">
                                        <i class="ki-duotone ki-paper-clip fs-3 text-gray-500 me-2"></i>
                                        <span class="text-muted fs-7">Attach files (coming soon)</span>
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

            {{-- Sidebar --}}
            <div class="col-xl-4">
                {{-- Ticket Info Card --}}
                <div class="card card-flush mb-5">
                    <div class="card-header pt-7">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-800">Ticket Information</span>
                            <span class="text-gray-500 mt-1 fw-semibold fs-6">Details about this support ticket</span>
                        </h3>
                    </div>

                    <div class="card-body pt-6">
                        {{-- Status Overview --}}
                        <div class="d-flex flex-stack mb-5">
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Status</div>
                            <div class="d-flex align-items-senter">
                                <span class="badge badge-{{ $config['color'] }} fs-7 fw-bold">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                            </div>
                        </div>

                        <div class="separator separator-dashed mb-5"></div>

                        {{-- Category --}}
                        <div class="d-flex flex-stack mb-5">
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Category</div>
                            <div class="text-gray-800 fw-bold fs-6">{{ ucfirst($ticket->category) }}</div>
                        </div>

                        <div class="separator separator-dashed mb-5"></div>

                        {{-- Priority --}}
                        <div class="d-flex flex-stack mb-5">
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Priority</div>
                            <div class="text-gray-800 fw-bold fs-6">{{ ucfirst($ticket->priority) }}</div>
                        </div>

                        <div class="separator separator-dashed mb-5"></div>

                        {{-- Created Date --}}
                        <div class="d-flex flex-stack mb-5">
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Created</div>
                            <div class="text-gray-800 fw-bold fs-6">
                                {{ $ticket->created_at->format('M d, Y') }}
                            </div>
                        </div>

                        <div class="separator separator-dashed mb-5"></div>

                        {{-- Last Updated --}}
                        <div class="d-flex flex-stack mb-5">
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Last Updated</div>
                            <div class="text-gray-800 fw-bold fs-6">
                                {{ $ticket->updated_at->diffForHumans() }}
                            </div>
                        </div>

                        <div class="separator separator-dashed mb-5"></div>

                        {{-- Messages Count --}}
                        <div class="d-flex flex-stack">
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Total Messages</div>
                            <div class="text-gray-800 fw-bold fs-6">{{ $ticket->messages->count() }}</div>
                        </div>
                    </div>
                </div>

                {{-- Help Card --}}
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
                                    Our support team typically responds within 24 hours
                                </p>
                            </div>
                        </div>

                        <div class="separator separator-dashed border-info mb-5"></div>

                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="ki-duotone ki-question-2 fs-2 text-info me-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                <a href="#" class="text-info fw-bold text-hover-primary">Browse FAQs</a>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="ki-duotone ki-message-text-2 fs-2 text-info me-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                <a href="#" class="text-info fw-bold text-hover-primary">View All Tickets</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</x-default-layout>

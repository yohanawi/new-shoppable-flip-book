<x-default-layout>

    @section('title')
        Notification Audit
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('admin.notifications.index') }}
    @endsection

    @if (session('success'))
        <div class="alert alert-success mb-8">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-8">
            <div class="fw-bold mb-2">Notification action could not be completed.</div>
            <ul class="mb-0 ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-6 g-xl-9 mb-8">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>Send Custom Notification</h2>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('admin.notifications.send') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label">Recipients</label>
                            <select name="user_ids[]" class="form-select form-select-solid" multiple size="10"
                                required>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Use Ctrl/Cmd to select multiple recipients.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control form-control-solid" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Message</label>
                            <textarea name="message" rows="5" class="form-control form-control-solid" required></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Action URL</label>
                            <input type="url" name="action_url" class="form-control form-control-solid">
                        </div>
                        <div class="mb-6">
                            <label class="form-label">Action Text</label>
                            <input type="text" name="action_text" class="form-control form-control-solid"
                                placeholder="Open">
                        </div>
                        <button type="submit" class="btn btn-primary">Send Notification</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>Notification Audit</h2>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Recipient</th>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Sent</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-700">
                                @forelse ($notifications as $notification)
                                    <tr>
                                        <td>
                                            @php($recipient = $users->firstWhere('id', $notification->notifiable_id))
                                            <div class="d-flex flex-column">
                                                <span
                                                    class="text-gray-900">{{ $recipient?->name ?? 'User #' . $notification->notifiable_id }}</span>
                                                <span class="text-muted fs-7">{{ $recipient?->email }}</span>
                                            </div>
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::headline(class_basename($notification->type)) }}
                                        </td>
                                        <td>{{ data_get($notification->data, 'title', 'Notification') }}</td>
                                        <td>
                                            <span
                                                class="badge badge-light-{{ $notification->read_at ? 'success' : 'warning' }}">
                                                {{ $notification->read_at ? 'Read' : 'Unread' }}
                                            </span>
                                        </td>
                                        <td>{{ $notification->created_at?->format('d M Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-8">No notifications have
                                            been sent yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($notifications->hasPages())
                    <div class="card-footer">{{ $notifications->links() }}</div>
                @endif
            </div>
        </div>
    </div>

</x-default-layout>

<x-default-layout>

    @section('title')
        Ticket Categories
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('tickets.categories.index') }}
    @endsection

    <div id="kt_app_content_container">
        <div class="row g-7">
            <div class="col-xl-4">
                <div class="card card-flush h-xl-100">
                    <div class="card-header pt-7">
                        <div class="card-title d-flex flex-column">
                            <span class="fw-bold fs-3">Create Category</span>
                            <span class="text-muted fs-7">Add categories customers can choose when opening a
                                ticket.</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form method="POST" action="{{ route('tickets.categories.store') }}">
                            @csrf

                            <div class="mb-6">
                                <label class="form-label required fw-semibold">Name</label>
                                <input type="text" name="name"
                                    class="form-control form-control-solid @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}" placeholder="Technical" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-6">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" rows="3"
                                    class="form-control form-control-solid @error('description') is-invalid @enderror"
                                    placeholder="Explain when this category should be used.">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-check form-switch form-check-custom form-check-solid mb-8">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" id="create_category_active"
                                    name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold text-gray-700" for="create_category_active">
                                    Active category
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Create Category</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card card-flush">
                    <div class="card-header pt-7">
                        <div class="card-title d-flex flex-column">
                            <span class="fw-bold fs-3">Manage Categories</span>
                            <span class="text-muted fs-7">Edit labels, descriptions, and availability for support
                                teams.</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @forelse ($categories as $category)
                            <div class="border border-dashed border-gray-300 rounded p-6 mb-6">
                                <div
                                    class="d-flex flex-column flex-lg-row gap-6 justify-content-between align-items-lg-start">
                                    <div class="flex-grow-1">
                                        <form method="POST"
                                            action="{{ route('tickets.categories.update', $category) }}">
                                            @csrf
                                            @method('PUT')

                                            <div class="row g-4">
                                                <div class="col-lg-4">
                                                    <label class="form-label fw-semibold">Name</label>
                                                    <input type="text" name="name"
                                                        class="form-control form-control-solid"
                                                        value="{{ $category->name }}" required>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="form-label fw-semibold">Slug</label>
                                                    <input type="text" name="slug"
                                                        class="form-control form-control-solid"
                                                        value="{{ $category->slug }}" required>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="form-label fw-semibold">Status</label>
                                                    <select name="is_active" class="form-select form-select-solid"
                                                        data-control="select2" data-hide-search="true">
                                                        <option value="1"
                                                            {{ $category->is_active ? 'selected' : '' }}>Active
                                                        </option>
                                                        <option value="0"
                                                            {{ !$category->is_active ? 'selected' : '' }}>Inactive
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-lg-2 d-flex align-items-end">
                                                    <button type="submit"
                                                        class="btn btn-light-primary w-100">Save</button>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold">Description</label>
                                                    <textarea name="description" rows="2" class="form-control form-control-solid" placeholder="Category description">{{ $category->description }}</textarea>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="d-flex flex-column align-items-lg-end gap-3">
                                        <span
                                            class="badge {{ $category->is_active ? 'badge-light-success' : 'badge-light-secondary' }} fs-7">
                                            {{ $category->tickets_count }} tickets
                                        </span>
                                        <form method="POST"
                                            action="{{ route('tickets.categories.destroy', $category) }}"
                                            data-swal-confirm data-swal-title="Delete category?"
                                            data-swal-text="Tickets already linked to this category will keep their history, but the category will be removed from admin management."
                                            data-swal-icon="warning" data-swal-confirm-text="Yes, delete it"
                                            data-swal-cancel-text="Keep category">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-15 text-muted">
                                No categories found.
                            </div>
                        @endforelse

                        @if ($categories->hasPages())
                            <div class="pt-4">
                                {{ $categories->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-default-layout>

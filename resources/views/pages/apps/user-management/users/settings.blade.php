<x-default-layout>

    @section('title')
        Users - Account Settings
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('user-management.users.settings') }}
    @endsection

    <div class="d-flex flex-column flex-lg-row">
        <div class="flex-column flex-lg-row-auto w-lg-250px w-xl-350px mb-10">
            <div class="card mb-5 mb-xl-8">
                <div class="card-body">
                    <div class="d-flex flex-center flex-column py-5">
                        <div class="symbol symbol-100px symbol-circle mb-7">
                            @if ($user->profile_photo_url)
                                <img src="{{ $user->profile_photo_url }}" alt="image" />
                            @else
                                <div
                                    class="symbol-label fs-3 {{ app(\App\Actions\GetThemeType::class)->handle('bg-light-? text-?', $user->name) }}">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <a href="#" class="fs-3 text-gray-800 text-hover-primary fw-bold mb-3">
                            {{ $user->name }}                    
                        </a>
                        <div class="mb-9">
                            @foreach ($user->roles as $role)
                                <div class="badge badge-lg badge-light-primary d-inline">
                                    {{ ucwords($role->name) }}
                                </div>
                            @endforeach
                        </div>
                        <div class="fw-bold mb-3">Assigned Tickets
                            <span class="ms-2" ddata-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true"
                                data-bs-content="Number of support tickets assigned, closed and pending this week.">
                                <i class="ki-duotone ki-information fs-7">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </span>
                        </div>
                        <div class="d-flex flex-wrap flex-center">
                            <div class="border border-gray-300 border-dashed rounded py-3 px-3 mb-3">
                                <div class="fs-4 fw-bold text-gray-700">
                                    <span class="w-75px">243</span>
                                    <i class="ki-duotone ki-arrow-up fs-3 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="fw-semibold text-muted">Total</div>
                            </div>
                            <div class="border border-gray-300 border-dashed rounded py-3 px-3 mx-4 mb-3">
                                <div class="fs-4 fw-bold text-gray-700">
                                    <span class="w-50px">56</span>
                                    <i class="ki-duotone ki-arrow-down fs-3 text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="fw-semibold text-muted">Solved</div>
                            </div>
                            <div class="border border-gray-300 border-dashed rounded py-3 px-3 mb-3">
                                <div class="fs-4 fw-bold text-gray-700">
                                    <span class="w-50px">188</span>
                                    <i class="ki-duotone ki-arrow-up fs-3 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="fw-semibold text-muted">Open</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-stack fs-4 py-3">
                        <div class="fw-bold rotate collapsible" data-bs-toggle="collapse" href="#kt_user_view_details"
                            role="button" aria-expanded="false" aria-controls="kt_user_view_details">Details
                            <span class="ms-2 rotate-180">
                                <i class="ki-duotone ki-down fs-3"></i>
                            </span>
                        </div>
                        <span data-bs-toggle="tooltip" data-bs-trigger="hover" title="Edit customer details">
                            <a href="#" class="btn btn-sm btn-light-primary" data-bs-toggle="modal"
                                data-bs-target="#kt_modal_update_details">Edit</a>
                        </span>
                    </div>
                    <div class="separator"></div>
                    <div id="kt_user_view_details" class="collapse show">
                        <div class="pb-5 fs-6">
                            <div class="fw-bold mt-5">Account ID</div>
                            <div class="text-gray-600">ID-45453423</div>
                            <div class="fw-bold mt-5">Email</div>
                            <div class="text-gray-600">
                                <a href="#" class="text-gray-600 text-hover-primary">info@keenthemes.com</a>
                            </div>
                            <div class="fw-bold mt-5">Address</div>
                            <div class="text-gray-600">101 Collin Street,
                                <br />Melbourne 3000 VIC
                                <br />Australia
                            </div>                           
                            <div class="fw-bold mt-5">Last Login</div>
                            <div class="text-gray-600">05 May 2023, 9:23 pm</div>
                        </div>
                    </div>
                </div>
            </div>          
        </div>
        <div class="flex-lg-row-fluid ms-lg-15">
            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                        href="#kt_user_view_overview_tab">Overview</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                        href="#kt_user_view_overview_security">Security</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_user_view_overview_events_and_logs_tab">Logs</a>
                </li>
                <li class="nav-item ms-auto">
                    <a href="#" class="btn btn-primary ps-7" data-kt-menu-trigger="click"
                        data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">Actions
                        <i class="ki-duotone ki-down fs-2 me-0"></i></a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold py-4 w-250px fs-6"
                        data-kt-menu="true">                       
                        <div class="menu-item px-5">
                            <div class="menu-content text-muted pb-2 px-5 fs-7 text-uppercase">Account</div>
                        </div>
                        <div class="menu-item px-5">
                            <a href="#" class="menu-link text-danger px-5">Delete customer</a>
                        </div>
                    </div>
                </li>
            </ul>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="kt_user_view_overview_tab" role="tabpanel">                    
                    <div class="card card-flush mb-6 mb-xl-9">
                        <div class="card-header mt-6">
                            <div class="card-title flex-column">
                                <h2 class="mb-1">User's Tasks</h2>
                                <div class="fs-6 fw-semibold text-muted">Total 25 tasks in backlog</div>
                            </div>
                            <div class="card-toolbar">
                                <button type="button" class="btn btn-light-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#kt_modal_add_task">
                                    <i class="ki-duotone ki-add-files fs-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>Add Task</button>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center position-relative mb-7">
                                <div class="position-absolute top-0 start-0 rounded h-100 bg-secondary w-4px"></div>
                                <div class="fw-semibold ms-5">
                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary">Create
                                        FureStibe branding logo</a>
                                    <div class="fs-7 text-muted">Due in 1 day
                                        <a href="#">Karina Clark</a>
                                    </div>
                                </div>
                                <button type="button"
                                    class="btn btn-icon btn-active-light-primary w-30px h-30px ms-auto"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                    <i class="ki-duotone ki-setting-3 fs-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                    </i>
                                </button>
                                <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true"
                                    data-kt-menu-id="kt-users-tasks">
                                    <div class="px-7 py-5">
                                        <div class="fs-5 text-gray-900 fw-bold">Update Status</div>
                                    </div>
                                    <div class="separator border-gray-200"></div>
                                    <form class="form px-7 py-5" data-kt-menu-id="kt-users-tasks-form">
                                        <div class="fv-row mb-10">
                                            <label class="form-label fs-6 fw-semibold">Status:</label>
                                            <select class="form-select form-select-solid" name="task_status"
                                                data-kt-select2="true" data-placeholder="Select option"
                                                data-allow-clear="true" data-hide-search="true">
                                                <option></option>
                                                <option value="1">Approved</option>
                                                <option value="2">Pending</option>
                                                <option value="3">In Process</option>
                                                <option value="4">Rejected</option>
                                            </select>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button"
                                                class="btn btn-sm btn-light btn-active-light-primary me-2"
                                                data-kt-users-update-task-status="reset">Reset</button>
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                data-kt-users-update-task-status="submit">
                                                <span class="indicator-label">Apply</span>
                                                <span class="indicator-progress">Please wait...
                                                    <span
                                                        class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="d-flex align-items-center position-relative mb-7">
                                <div class="position-absolute top-0 start-0 rounded h-100 bg-secondary w-4px"></div>
                                <div class="fw-semibold ms-5">
                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary">Schedule a
                                        meeting with FireBear CTO John</a>
                                    <div class="fs-7 text-muted">Due in 3 days
                                        <a href="#">Rober Doe</a>
                                    </div>
                                </div>
                                <button type="button"
                                    class="btn btn-icon btn-active-light-primary w-30px h-30px ms-auto"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                    <i class="ki-duotone ki-setting-3 fs-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                    </i>
                                </button>
                                <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true"
                                    data-kt-menu-id="kt-users-tasks">
                                    <div class="px-7 py-5">
                                        <div class="fs-5 text-gray-900 fw-bold">Update Status</div>
                                    </div>
                                    <div class="separator border-gray-200"></div>
                                    <form class="form px-7 py-5" data-kt-menu-id="kt-users-tasks-form">
                                        <div class="fv-row mb-10">
                                            <label class="form-label fs-6 fw-semibold">Status:</label>
                                            <select class="form-select form-select-solid" name="task_status"
                                                data-kt-select2="true" data-placeholder="Select option"
                                                data-allow-clear="true" data-hide-search="true">
                                                <option></option>
                                                <option value="1">Approved</option>
                                                <option value="2">Pending</option>
                                                <option value="3">In Process</option>
                                                <option value="4">Rejected</option>
                                            </select>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button"
                                                class="btn btn-sm btn-light btn-active-light-primary me-2"
                                                data-kt-users-update-task-status="reset">Reset</button>
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                data-kt-users-update-task-status="submit">
                                                <span class="indicator-label">Apply</span>
                                                <span class="indicator-progress">Please wait...
                                                    <span
                                                        class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="d-flex align-items-center position-relative mb-7">
                                <div class="position-absolute top-0 start-0 rounded h-100 bg-secondary w-4px"></div>
                                <div class="fw-semibold ms-5">
                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary">9 Degree
                                        Project Estimation</a>
                                    <div class="fs-7 text-muted">Due in 1 week
                                        <a href="#">Neil Owen</a>
                                    </div>
                                </div>
                                <button type="button"
                                    class="btn btn-icon btn-active-light-primary w-30px h-30px ms-auto"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                    <i class="ki-duotone ki-setting-3 fs-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                    </i>
                                </button>
                                <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true"
                                    data-kt-menu-id="kt-users-tasks">
                                    <div class="px-7 py-5">
                                        <div class="fs-5 text-gray-900 fw-bold">Update Status</div>
                                    </div>
                                    <div class="separator border-gray-200"></div>
                                    <form class="form px-7 py-5" data-kt-menu-id="kt-users-tasks-form">
                                        <div class="fv-row mb-10">
                                            <label class="form-label fs-6 fw-semibold">Status:</label>
                                            <select class="form-select form-select-solid" name="task_status"
                                                data-kt-select2="true" data-placeholder="Select option"
                                                data-allow-clear="true" data-hide-search="true">
                                                <option></option>
                                                <option value="1">Approved</option>
                                                <option value="2">Pending</option>
                                                <option value="3">In Process</option>
                                                <option value="4">Rejected</option>
                                            </select>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button"
                                                class="btn btn-sm btn-light btn-active-light-primary me-2"
                                                data-kt-users-update-task-status="reset">Reset</button>
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                data-kt-users-update-task-status="submit">
                                                <span class="indicator-label">Apply</span>
                                                <span class="indicator-progress">Please wait...
                                                    <span
                                                        class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="d-flex align-items-center position-relative mb-7">
                                <div class="position-absolute top-0 start-0 rounded h-100 bg-secondary w-4px"></div>
                                <div class="fw-semibold ms-5">
                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary">Dashboard
                                        UI & UX for Leafr CRM</a>
                                    <div class="fs-7 text-muted">Due in 1 week
                                        <a href="#">Olivia Wild</a>
                                    </div>
                                </div>
                                <button type="button"
                                    class="btn btn-icon btn-active-light-primary w-30px h-30px ms-auto"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                    <i class="ki-duotone ki-setting-3 fs-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                    </i>
                                </button>
                                <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true"
                                    data-kt-menu-id="kt-users-tasks">
                                    <div class="px-7 py-5">
                                        <div class="fs-5 text-gray-900 fw-bold">Update Status</div>
                                    </div>
                                    <div class="separator border-gray-200"></div>
                                    <form class="form px-7 py-5" data-kt-menu-id="kt-users-tasks-form">
                                        <div class="fv-row mb-10">
                                            <label class="form-label fs-6 fw-semibold">Status:</label>
                                            <select class="form-select form-select-solid" name="task_status"
                                                data-kt-select2="true" data-placeholder="Select option"
                                                data-allow-clear="true" data-hide-search="true">
                                                <option></option>
                                                <option value="1">Approved</option>
                                                <option value="2">Pending</option>
                                                <option value="3">In Process</option>
                                                <option value="4">Rejected</option>
                                            </select>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button"
                                                class="btn btn-sm btn-light btn-active-light-primary me-2"
                                                data-kt-users-update-task-status="reset">Reset</button>
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                data-kt-users-update-task-status="submit">
                                                <span class="indicator-label">Apply</span>
                                                <span class="indicator-progress">Please wait...
                                                    <span
                                                        class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="d-flex align-items-center position-relative">
                                <div class="position-absolute top-0 start-0 rounded h-100 bg-secondary w-4px"></div>
                                <div class="fw-semibold ms-5">
                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary">Mivy App
                                        R&D, Meeting with clients</a>
                                    <div class="fs-7 text-muted">Due in 2 weeks
                                        <a href="#">Sean Bean</a>
                                    </div>
                                </div>
                                <button type="button"
                                    class="btn btn-icon btn-active-light-primary w-30px h-30px ms-auto"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                    <i class="ki-duotone ki-setting-3 fs-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                    </i>
                                </button>
                                <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true"
                                    data-kt-menu-id="kt-users-tasks">
                                    <div class="px-7 py-5">
                                        <div class="fs-5 text-gray-900 fw-bold">Update Status</div>
                                    </div>
                                    <div class="separator border-gray-200"></div>
                                    <form class="form px-7 py-5" data-kt-menu-id="kt-users-tasks-form">
                                        <div class="fv-row mb-10">
                                            <label class="form-label fs-6 fw-semibold">Status:</label>
                                            <select class="form-select form-select-solid" name="task_status"
                                                data-kt-select2="true" data-placeholder="Select option"
                                                data-allow-clear="true" data-hide-search="true">
                                                <option></option>
                                                <option value="1">Approved</option>
                                                <option value="2">Pending</option>
                                                <option value="3">In Process</option>
                                                <option value="4">Rejected</option>
                                            </select>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button"
                                                class="btn btn-sm btn-light btn-active-light-primary me-2"
                                                data-kt-users-update-task-status="reset">Reset</button>
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                data-kt-users-update-task-status="submit">
                                                <span class="indicator-label">Apply</span>
                                                <span class="indicator-progress">Please wait...
                                                    <span
                                                        class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="kt_user_view_overview_security" role="tabpanel">
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2>Profile</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0 pb-5">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-5"
                                    id="kt_table_users_login_session">
                                    <tbody class="fs-6 fw-semibold text-gray-600">
                                        <tr>
                                            <td>Email</td>
                                            <td>smith@kpmg.com</td>
                                            <td class="text-end">
                                                <button type="button"
                                                    class="btn btn-icon btn-active-light-primary w-30px h-30px ms-auto"
                                                    data-bs-toggle="modal" data-bs-target="#kt_modal_update_email">
                                                    <i class="ki-duotone ki-pencil fs-3">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Password</td>
                                            <td>******</td>
                                            <td class="text-end">
                                                <button type="button"
                                                    class="btn btn-icon btn-active-light-primary w-30px h-30px ms-auto"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#kt_modal_update_password">
                                                    <i class="ki-duotone ki-pencil fs-3">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Role</td>
                                            <td>Administrator</td>
                                            <td class="text-end">
                                                <button type="button"
                                                    class="btn btn-icon btn-active-light-primary w-30px h-30px ms-auto"
                                                    data-bs-toggle="modal" data-bs-target="#kt_modal_update_role">
                                                    <i class="ki-duotone ki-pencil fs-3">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <div class="card-header border-0">
                            <div class="card-title flex-column">
                                <h2 class="mb-1">Two Step Authentication</h2>
                                <div class="fs-6 fw-semibold text-muted">Keep your account extra secure with a second
                                    authentication step.</div>
                            </div>
                            <div class="card-toolbar">
                                <button type="button" class="btn btn-light-primary btn-sm"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                    <i class="ki-duotone ki-fingerprint-scanning fs-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                    </i>Add Authentication Step</button>
                                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-6 w-200px py-4"
                                    data-kt-menu="true">
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3" data-bs-toggle="modal"
                                            data-bs-target="#kt_modal_add_auth_app">Use authenticator app</a>
                                    </div>
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3" data-bs-toggle="modal"
                                            data-bs-target="#kt_modal_add_one_time_password">Enable one-time
                                            password</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pb-5">
                            <div class="d-flex flex-stack">
                                <div class="d-flex flex-column">
                                    <span>SMS</span>
                                    <span class="text-muted fs-6">+61 412 345 678</span>
                                </div>
                                <div class="d-flex justify-content-end align-items-center">
                                    <button type="button"
                                        class="btn btn-icon btn-active-light-primary w-30px h-30px ms-auto me-5"
                                        data-bs-toggle="modal" data-bs-target="#kt_modal_add_one_time_password">
                                        <i class="ki-duotone ki-pencil fs-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </button>
                                    <button type="button"
                                        class="btn btn-icon btn-active-light-primary w-30px h-30px ms-auto"
                                        id="kt_users_delete_two_step">
                                        <i class="ki-duotone ki-trash fs-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                            <span class="path5"></span>
                                        </i>
                                    </button>
                                </div>
                            </div>
                            <div class="separator separator-dashed my-5"></div>
                            <div class="text-gray-600">If you lose your mobile device or security key, you can
                                <a href='#' class="me-1">generate a backup code</a>to sign in to your
                                account.
                            </div>
                        </div>
                    </div>
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <div class="card-header border-0">
                            <div class="card-title flex-column">
                                <h2>Email Notifications</h2>
                                <div class="fs-6 fw-semibold text-muted">Choose what messages you’d like to receive
                                    for each of your accounts.</div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form class="form" id="kt_users_email_notification_form">
                                <div class="d-flex">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" name="email_notification_0"
                                            type="checkbox" value="0"
                                            id="kt_modal_update_email_notification_0" checked='checked' />
                                        <label class="form-check-label" for="kt_modal_update_email_notification_0">
                                            <div class="fw-bold">Successful Payments</div>
                                            <div class="text-gray-600">Receive a notification for every successful
                                                payment.</div>
                                        </label>
                                    </div>
                                </div>
                                <div class='separator separator-dashed my-5'></div>
                                <div class="d-flex">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" name="email_notification_1"
                                            type="checkbox" value="1"
                                            id="kt_modal_update_email_notification_1" />
                                        <label class="form-check-label" for="kt_modal_update_email_notification_1">
                                            <div class="fw-bold">Payouts</div>
                                            <div class="text-gray-600">Receive a notification for every initiated
                                                payout.</div>
                                        </label>
                                    </div>
                                </div>
                                <div class='separator separator-dashed my-5'></div>
                                <div class="d-flex">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" name="email_notification_2"
                                            type="checkbox" value="2"
                                            id="kt_modal_update_email_notification_2" />
                                        <label class="form-check-label" for="kt_modal_update_email_notification_2">
                                            <div class="fw-bold">Application fees</div>
                                            <div class="text-gray-600">Receive a notification each time you collect a
                                                fee from an account.</div>
                                        </label>
                                    </div>
                                </div>
                                <div class='separator separator-dashed my-5'></div>
                                <div class="d-flex">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" name="email_notification_3"
                                            type="checkbox" value="3"
                                            id="kt_modal_update_email_notification_3" checked='checked' />
                                        <label class="form-check-label" for="kt_modal_update_email_notification_3">
                                            <div class="fw-bold">Disputes</div>
                                            <div class="text-gray-600">Receive a notification if a payment is disputed
                                                by a customer and for dispute resolutions.</div>
                                        </label>
                                    </div>
                                </div>
                                <div class='separator separator-dashed my-5'></div>
                                <div class="d-flex">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" name="email_notification_4"
                                            type="checkbox" value="4"
                                            id="kt_modal_update_email_notification_4" checked='checked' />
                                        <label class="form-check-label" for="kt_modal_update_email_notification_4">
                                            <div class="fw-bold">Payment reviews</div>
                                            <div class="text-gray-600">Receive a notification if a payment is marked
                                                as an elevated risk.</div>
                                        </label>
                                    </div>
                                </div>
                                <div class='separator separator-dashed my-5'></div>
                                <div class="d-flex">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" name="email_notification_5"
                                            type="checkbox" value="5"
                                            id="kt_modal_update_email_notification_5" />
                                        <label class="form-check-label" for="kt_modal_update_email_notification_5">
                                            <div class="fw-bold">Mentions</div>
                                            <div class="text-gray-600">Receive a notification if a teammate mentions
                                                you in a note.</div>
                                        </label>
                                    </div>
                                </div>
                                <div class='separator separator-dashed my-5'></div>
                                <div class="d-flex">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" name="email_notification_6"
                                            type="checkbox" value="6"
                                            id="kt_modal_update_email_notification_6" />
                                        <label class="form-check-label" for="kt_modal_update_email_notification_6">
                                            <div class="fw-bold">Invoice Mispayments</div>
                                            <div class="text-gray-600">Receive a notification if a customer sends an
                                                incorrect amount to pay their invoice.</div>
                                        </label>
                                    </div>
                                </div>
                                <div class='separator separator-dashed my-5'></div>
                                <div class="d-flex">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" name="email_notification_7"
                                            type="checkbox" value="7"
                                            id="kt_modal_update_email_notification_7" />
                                        <label class="form-check-label" for="kt_modal_update_email_notification_7">
                                            <div class="fw-bold">Webhooks</div>
                                            <div class="text-gray-600">Receive notifications about consistently
                                                failing webhook endpoints.</div>
                                        </label>
                                    </div>
                                </div>
                                <div class='separator separator-dashed my-5'></div>
                                <div class="d-flex">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" name="email_notification_8"
                                            type="checkbox" value="8"
                                            id="kt_modal_update_email_notification_8" />
                                        <label class="form-check-label" for="kt_modal_update_email_notification_8">
                                            <div class="fw-bold">Trial</div>
                                            <div class="text-gray-600">Receive helpful tips when you try out our
                                                products.</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end align-items-center mt-12">
                                    <button type="button" class="btn btn-light me-5"
                                        id="kt_users_email_notification_cancel">Cancel</button>
                                    <button type="button" class="btn btn-primary"
                                        id="kt_users_email_notification_submit">
                                        <span class="indicator-label">Save</span>
                                        <span class="indicator-progress">Please wait...
                                            <span
                                                class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="kt_user_view_overview_events_and_logs_tab" role="tabpanel">
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2>Login Sessions</h2>
                            </div>
                            <div class="card-toolbar">
                                <button type="button" class="btn btn-sm btn-flex btn-light-primary"
                                    id="kt_modal_sign_out_sesions">
                                    <i class="ki-duotone ki-entrance-right fs-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>Sign out all sessions</button>
                            </div>
                        </div>
                        <div class="card-body pt-0 pb-5">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-5"
                                    id="kt_table_users_login_session">
                                    <thead class="border-bottom border-gray-200 fs-7 fw-bold">
                                        <tr class="text-start text-muted text-uppercase gs-0">
                                            <th class="min-w-100px">Location</th>
                                            <th>Device</th>
                                            <th>IP Address</th>
                                            <th class="min-w-125px">Time</th>
                                            <th class="min-w-70px">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fs-6 fw-semibold text-gray-600">
                                        <tr>
                                            <td>Australia</td>
                                            <td>Chome - Windows</td>
                                            <td>207.20.21.295</td>
                                            <td>23 seconds ago</td>
                                            <td>Current session</td>
                                        </tr>
                                        <tr>
                                            <td>Australia</td>
                                            <td>Safari - iOS</td>
                                            <td>207.15.21.72</td>
                                            <td>3 days ago</td>
                                            <td>
                                                <a href="#" data-kt-users-sign-out="single_user">Sign out</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Australia</td>
                                            <td>Chrome - Windows</td>
                                            <td>207.10.28.325</td>
                                            <td>last week</td>
                                            <td>Expired</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2>Logs</h2>
                            </div>
                            <div class="card-toolbar">
                                <button type="button" class="btn btn-sm btn-light-primary">
                                    <i class="ki-duotone ki-cloud-download fs-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>Download Report</button>
                            </div>
                        </div>
                        <div class="card-body py-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fw-semibold text-gray-600 fs-6 gy-5"
                                    id="kt_table_users_logs">
                                    <tbody>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-danger">500 ERR</div>
                                            </td>
                                            <td>POST /v1/invoice/in_6877_1633/invalid</td>
                                            <td class="pe-0 text-end min-w-200px">22 Sep 2023, 6:05 pm</td>
                                        </tr>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-danger">500 ERR</div>
                                            </td>
                                            <td>POST /v1/invoice/in_6877_1633/invalid</td>
                                            <td class="pe-0 text-end min-w-200px">25 Oct 2023, 11:30 am</td>
                                        </tr>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-success">200 OK</div>
                                            </td>
                                            <td>POST /v1/invoices/in_5648_7203/payment</td>
                                            <td class="pe-0 text-end min-w-200px">15 Apr 2023, 6:43 am</td>
                                        </tr>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-danger">500 ERR</div>
                                            </td>
                                            <td>POST /v1/invoice/in_6877_1633/invalid</td>
                                            <td class="pe-0 text-end min-w-200px">25 Oct 2023, 8:43 pm</td>
                                        </tr>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-success">200 OK</div>
                                            </td>
                                            <td>POST /v1/invoices/in_1431_5657/payment</td>
                                            <td class="pe-0 text-end min-w-200px">21 Feb 2023, 11:05 am</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>                   
                </div>
            </div>
        </div>
    </div>
    @include('pages/apps/user-management/users/modals/_update-details')
    @include('pages/apps/user-management/users/modals/_add-one-time-password')
    @include('pages/apps/user-management/users/modals/_update-email')
    @include('pages/apps/user-management/users/modals/_update-password')
    @include('pages/apps/user-management/users/modals/_update-role')
    @include('pages/apps/user-management/users/modals/_add-task')

</x-default-layout>

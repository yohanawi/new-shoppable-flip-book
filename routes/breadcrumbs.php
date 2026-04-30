<?php

use App\Models\User;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Spatie\Permission\Models\Role;

// Home
Breadcrumbs::for('home', function (BreadcrumbTrail $trail) {
    $trail->push('Home', route('dashboard'));
});

// Home > Dashboard
Breadcrumbs::for('dashboard', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Dashboard', route('dashboard'));
});

// Home > Dashboard > Analytics
Breadcrumbs::for('analytics.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Analytics', route('analytics.index'));
});

Breadcrumbs::for('billing.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Billing', route('billing.index'));
});

Breadcrumbs::for('billing.plans', function (BreadcrumbTrail $trail) {
    $trail->parent('billing.index');
    $trail->push('Plans', route('billing.plans'));
});

Breadcrumbs::for('billing.payment-methods.index', function (BreadcrumbTrail $trail) {
    $trail->parent('billing.index');
    $trail->push('Payment Methods', route('billing.payment-methods.index'));
});

Breadcrumbs::for('billing.invoices.index', function (BreadcrumbTrail $trail) {
    $trail->parent('billing.index');
    $trail->push('Invoices and Activity', route('billing.invoices.index'));
});

Breadcrumbs::for('billing.payments.create', function (BreadcrumbTrail $trail) {
    $trail->parent('billing.index');
    $trail->push('Submit Payment', route('billing.payments.create'));
});

Breadcrumbs::for('billing.payments.history', function (BreadcrumbTrail $trail) {
    $trail->parent('billing.index');
    $trail->push('Payment History', route('billing.payments.history'));
});

Breadcrumbs::for('billing.payments.show', function (BreadcrumbTrail $trail, $paymentRequest) {
    $trail->parent('billing.payments.history');
    $trail->push($paymentRequest->requestNumber(), route('billing.payments.show', $paymentRequest));
});

Breadcrumbs::for('admin.billing.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Billing Dashboard', route('admin.billing.index'));
});

Breadcrumbs::for('notifications.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Notifications', route('notifications.index'));
});

Breadcrumbs::for('admin.notifications.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Notification Audit', route('admin.notifications.index'));
});

Breadcrumbs::for('admin.customers.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Customers', route('admin.customers.index'));
});

Breadcrumbs::for('admin.customers.show', function (BreadcrumbTrail $trail, User $customer) {
    $trail->parent('admin.customers.index');
    $trail->push($customer->name, route('admin.customers.show', $customer));
});

// Home > Dashboard > User Management
Breadcrumbs::for('user-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('User Management', route('user-management.users.index'));
});

// Home > Dashboard > User Management > Users
Breadcrumbs::for('user-management.users.index', function (BreadcrumbTrail $trail) {
    $trail->parent('user-management.index');
    $trail->push('Users', route('user-management.users.index'));
});

// Home > Dashboard > User Management > Users > [User]
Breadcrumbs::for('user-management.users.show', function (BreadcrumbTrail $trail, User $user) {
    $trail->parent('user-management.users.index');
    $trail->push(ucwords($user->name), route('user-management.users.show', $user));
});

// Home > Dashboard > User Management > Roles
Breadcrumbs::for('user-management.roles.index', function (BreadcrumbTrail $trail) {
    $trail->parent('user-management.index');
    $trail->push('Roles', route('user-management.roles.index'));
});

// Home > Dashboard > User Management > Roles > [Role]
Breadcrumbs::for('user-management.roles.show', function (BreadcrumbTrail $trail, Role $role) {
    $trail->parent('user-management.roles.index');
    $trail->push(ucwords($role->name), route('user-management.roles.show', $role));
});

// Home > Dashboard > User Management > Permission
Breadcrumbs::for('user-management.permissions.index', function (BreadcrumbTrail $trail) {
    $trail->parent('user-management.index');
    $trail->push('Permissions', route('user-management.permissions.index'));
});

// Home > Dashboard > Account Settings
Breadcrumbs::for('user-management.users.settings', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Account Settings', route('account.settings'));
});

// Home > Dashboard > Account Profile
Breadcrumbs::for('user-management.users.profile', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('My Profile', route('account.profile'));
});

// Home > Dashboard > Tickets
Breadcrumbs::for('tickets.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Support Tickets', route('tickets.index'));
});

// Home > Dashboard > Tickets > Create
Breadcrumbs::for('tickets.create', function (BreadcrumbTrail $trail) {
    $trail->parent('tickets.index');
    $trail->push('Create Ticket', route('tickets.create'));
});

// Home > Dashboard > Tickets > [Ticket]
Breadcrumbs::for('tickets.show', function (BreadcrumbTrail $trail, $ticket) {
    $trail->parent('tickets.index');
    $trail->push('Ticket #' . $ticket->id, route('tickets.show', $ticket));
});

// Home > Dashboard > Tickets > Categories
Breadcrumbs::for('tickets.categories.index', function (BreadcrumbTrail $trail) {
    $trail->parent('tickets.index');
    $trail->push('Categories', route('tickets.categories.index'));
});

// Home > Dashboard > Catalog PDFs
Breadcrumbs::for('catalog.pdfs.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Catalog PDFs', route('catalog.pdfs.index'));
});

// Home > Dashboard > Catalog PDFs > Create
Breadcrumbs::for('catalog.pdfs.create', function (BreadcrumbTrail $trail) {
    $trail->parent('catalog.pdfs.index');
    $trail->push('Upload PDF', route('catalog.pdfs.create'));
});

// Home > Dashboard > Catalog PDFs > [PDF]
Breadcrumbs::for('catalog.pdfs.show', function (BreadcrumbTrail $trail, $pdf) {
    $trail->parent('catalog.pdfs.index');
    $trail->push($pdf->title ?? 'Untitled PDF', route('catalog.pdfs.show', $pdf));
});

// Home > Dashboard > Catalog PDFs > Share Preview Studio
Breadcrumbs::for('catalog.pdfs.share-preview.index', function (BreadcrumbTrail $trail) {
    $trail->parent('catalog.pdfs.index');
    $trail->push('Share Preview Studio', route('catalog.pdfs.share-preview.index'));
});

// Home > Dashboard > Catalog PDFs > Share Preview Studio > [PDF]
Breadcrumbs::for('catalog.pdfs.share-preview.edit', function (BreadcrumbTrail $trail, $pdf) {
    $trail->parent('catalog.pdfs.share-preview.index');
    $trail->push($pdf->title ?? 'Untitled PDF', route('catalog.pdfs.share-preview.edit', $pdf));
});

// Home > Dashboard > Catalog PDFs > [PDF] > live view
Breadcrumbs::for('catalog.pdfs.slicer.live', function (BreadcrumbTrail $trail, $pdf) {
    $trail->parent('catalog.pdfs.show', $pdf);
    $trail->push('Live View', route('catalog.pdfs.slicer.live', $pdf));
});

// Home > Dashboard > Catalog PDFs > [PDF] > Page Management
Breadcrumbs::for('catalog.pdfs.page-management', function (BreadcrumbTrail $trail, $pdf) {
    $trail->parent('catalog.pdfs.show', $pdf);
    $trail->push('Page Management', route('catalog.pdfs.manage', $pdf));
});


// Home > Dashboard > Catalog PDFs > [PDF] > Flip Physics Preview
Breadcrumbs::for('catalog.pdfs.flip-physics.preview', function (BreadcrumbTrail $trail, $pdf) {
    $trail->parent('catalog.pdfs.show', $pdf);
    $trail->push('Flip Physics Preview', route('catalog.pdfs.flip-physics.preview', $pdf));
});

// Home > Dashboard > Catalog PDFs > [PDF] > Slicer Share
Breadcrumbs::for('catalog.pdfs.flip-physics.share', function (BreadcrumbTrail $trail, $pdf) {
    $trail->parent('catalog.pdfs.show', $pdf);
    $trail->push('Flip Physics Share', route('catalog.pdfs.flip-physics.share', $pdf));
});

// Home > Dashboard > Catalog PDFs > [PDF] > Slicer Editor
Breadcrumbs::for('catalog.pdfs.slicer.edit', function (BreadcrumbTrail $trail, $pdf) {
    $trail->parent('catalog.pdfs.show', $pdf);
    $trail->push('Slicer (Shoppable) Editor', route('catalog.pdfs.slicer.edit', $pdf));
});

// Home > Dashboard > Catalog PDFs > [PDF] > Slicer Preview
Breadcrumbs::for('catalog.pdfs.slicer.preview', function (BreadcrumbTrail $trail, $pdf) {
    $trail->parent('catalog.pdfs.show', $pdf);
    $trail->push('Slicer (Shoppable) Preview', route('catalog.pdfs.slicer.preview', $pdf));
});

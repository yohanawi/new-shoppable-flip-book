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

// Home > Dashboard > Catalog PDFs > [PDF] > live preview
Breadcrumbs::for('catalog.pdfs.slicer.preview', function (BreadcrumbTrail $trail, $pdf) {
    $trail->parent('catalog.pdfs.show', $pdf);
    $trail->push('Preview', route('catalog.pdfs.slicer.preview', $pdf));
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
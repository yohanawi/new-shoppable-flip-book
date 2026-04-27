<?php

use App\Http\Controllers\Apps\PermissionManagementController;
use App\Http\Controllers\Apps\RoleManagementController;
use App\Http\Controllers\Apps\UserManagementController;
use App\Http\Controllers\Apps\CatalogPdfController;
use App\Http\Controllers\Apps\CatalogAnalyticsController;
use App\Http\Controllers\Apps\AdminCustomerController;
use App\Http\Controllers\Apps\CatalogPdfSharePreviewController;
use App\Http\Controllers\Apps\CatalogPdfPageManagementController;
use App\Http\Controllers\Apps\CatalogPdfFlipPhysicsController;
use App\Http\Controllers\Apps\CatalogPdfSlicerController;
use App\Http\Controllers\Billing\AdminBillingController;
use App\Http\Controllers\Billing\CustomerBillingController;
use App\Http\Controllers\Billing\StripeWebhookController;
use App\Http\Controllers\Notifications\AdminNotificationController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Apps\CustomerTicketController;
use App\Http\Controllers\Apps\SupportTicketCategoryController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/', [DashboardController::class, 'index']);

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/analytics', [CatalogAnalyticsController::class, 'index'])
        ->name('analytics.index');

    Route::name('user-management.')->middleware(['role:admin'])->group(function () {
        Route::resource('/user-management/users', UserManagementController::class);
        Route::resource('/user-management/roles', RoleManagementController::class);
        Route::resource('/user-management/permissions', PermissionManagementController::class);
    });

    Route::prefix('admin/customers')->name('admin.customers.')->middleware(['role:admin'])->group(function () {
        Route::get('/', [AdminCustomerController::class, 'index'])->name('index');
        Route::get('/{customer}', [AdminCustomerController::class, 'show'])->name('show');
    });

    // Customer Routes
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/profile', [UserManagementController::class, 'profile'])->name('profile');
        Route::get('/settings', [UserManagementController::class, 'settings'])->name('settings');
        Route::post('/settings', [UserManagementController::class, 'updateSettings'])->name('settings.update');
    });

    Route::prefix('billing')->name('billing.')->middleware(['role:customer'])->group(function () {
        Route::get('/', [CustomerBillingController::class, 'index'])
            ->middleware('permission:customer.billing.view')
            ->name('index');
        Route::post('/plans/{plan}/subscribe', [CustomerBillingController::class, 'subscribe'])
            ->middleware('permission:customer.plan.manage')
            ->name('subscribe');
        Route::post('/subscription/cancel', [CustomerBillingController::class, 'cancelSubscription'])
            ->middleware('permission:customer.subscription.manage')
            ->name('subscription.cancel');
        Route::post('/subscription/resume', [CustomerBillingController::class, 'resumeSubscription'])
            ->middleware('permission:customer.subscription.manage')
            ->name('subscription.resume');
        Route::get('/portal', [CustomerBillingController::class, 'billingPortal'])
            ->middleware('permission:customer.payment.view')
            ->name('portal');
        Route::post('/payment-methods', [CustomerBillingController::class, 'storePaymentMethod'])
            ->middleware('permission:customer.payment_method.manage')
            ->name('payment-methods.store');
        Route::post('/payment-methods/{paymentMethod}/default', [CustomerBillingController::class, 'setDefaultPaymentMethod'])
            ->middleware('permission:customer.payment_method.manage')
            ->name('payment-methods.default');
        Route::delete('/payment-methods/{paymentMethod}', [CustomerBillingController::class, 'destroyPaymentMethod'])
            ->middleware('permission:customer.payment_method.manage')
            ->name('payment-methods.destroy');
        Route::get('/invoices/{invoiceId}/download', [CustomerBillingController::class, 'downloadInvoice'])
            ->middleware('permission:customer.invoice.download')
            ->name('invoices.download');
    });

    Route::prefix('admin/billing')->name('admin.billing.')->middleware(['role:admin'])->group(function () {
        Route::get('/', [AdminBillingController::class, 'index'])
            ->middleware('permission:admin.billing.view')
            ->name('index');
        Route::post('/plans', [AdminBillingController::class, 'storePlan'])
            ->middleware('permission:admin.plan.manage')
            ->name('plans.store');
        Route::put('/plans/{plan}', [AdminBillingController::class, 'updatePlan'])
            ->middleware('permission:admin.plan.manage')
            ->name('plans.update');
        Route::delete('/plans/{plan}', [AdminBillingController::class, 'destroyPlan'])
            ->middleware('permission:admin.plan.manage')
            ->name('plans.destroy');
        Route::post('/subscriptions/{subscription}/swap', [AdminBillingController::class, 'swapSubscription'])
            ->middleware('permission:admin.subscription.manage')
            ->name('subscriptions.swap');
        Route::post('/subscriptions/{subscription}/cancel', [AdminBillingController::class, 'cancelSubscription'])
            ->middleware('permission:admin.subscription.manage')
            ->name('subscriptions.cancel');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])
            ->middleware('permission:notifications.view')
            ->name('index');
        Route::get('/feed', [NotificationController::class, 'feed'])
            ->middleware('permission:notifications.view')
            ->name('feed');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])
            ->middleware('permission:notifications.manage')
            ->name('read-all');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->middleware('permission:notifications.manage')
            ->name('read');
    });

    Route::prefix('admin/notifications')->name('admin.notifications.')->middleware(['role:admin'])->group(function () {
        Route::get('/', [AdminNotificationController::class, 'index'])
            ->middleware('permission:admin.notifications.view')
            ->name('index');
        Route::post('/send', [AdminNotificationController::class, 'send'])
            ->middleware('permission:admin.notifications.send')
            ->name('send');
    });

    // Support Tickets
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [CustomerTicketController::class, 'index'])->name('index');
        Route::get('/create', [CustomerTicketController::class, 'create'])->name('create');
        Route::post('/', [CustomerTicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [CustomerTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [CustomerTicketController::class, 'reply'])->name('reply');
        Route::patch('/{ticket}/status', [CustomerTicketController::class, 'updateStatus'])->name('status.update');
        Route::post('/{ticket}/feedback', [CustomerTicketController::class, 'storeFeedback'])->name('feedback.store');
    });

    Route::prefix('admin/ticket-categories')->name('tickets.categories.')->middleware(['role:admin'])->group(function () {
        Route::get('/', [SupportTicketCategoryController::class, 'index'])->name('index');
        Route::post('/', [SupportTicketCategoryController::class, 'store'])->name('store');
        Route::put('/{category}', [SupportTicketCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [SupportTicketCategoryController::class, 'destroy'])->name('destroy');
    });
});

Route::prefix('catalog/pdfs')->name('catalog.pdfs.')->group(function () {
    Route::get('/{catalogPdf}/share', [CatalogPdfController::class, 'share'])->name('share');
    Route::get('/{catalogPdf}/share-preview/assets/{asset}', [CatalogPdfSharePreviewController::class, 'asset'])->name('share-preview.asset');
    Route::get('/{catalogPdf}/flip-physics/share', [CatalogPdfController::class, 'share'])->name('flip-physics.share');
    Route::get('/{catalogPdf}/slicer/share', [CatalogPdfController::class, 'share'])->name('slicer.share');
    Route::get('/{catalogPdf}/file', [CatalogPdfController::class, 'file'])->name('file');
    Route::get('/{catalogPdf}/download', [CatalogPdfController::class, 'download'])->name('download');
    Route::post('/{catalogPdf}/analytics/track', [CatalogAnalyticsController::class, 'track'])->name('analytics.track');
    Route::get('/{catalogPdf}/slicer/pages/{page}/image', [CatalogPdfSlicerController::class, 'pageImage'])->name('slicer.pages.image');
    Route::get('/{catalogPdf}/slicer/hotspots/{hotspot}/media/{kind}', [CatalogPdfSlicerController::class, 'hotspotMedia'])->name('slicer.hotspots.media');
});

Route::middleware(['auth'])->group(function () {
    // Catalog PDFs
    Route::prefix('catalog/pdfs')->name('catalog.pdfs.')->group(function () {
        Route::get('/', [CatalogPdfController::class, 'index'])->name('index');
        Route::get('/create', [CatalogPdfController::class, 'create'])->name('create');
        Route::get('/share-preview', [CatalogPdfSharePreviewController::class, 'index'])->name('share-preview.index');
        Route::post('/', [CatalogPdfController::class, 'store'])->name('store');
        Route::get('/{catalogPdf}', [CatalogPdfController::class, 'show'])->name('show');
        Route::get('/{catalogPdf}/share-preview', [CatalogPdfSharePreviewController::class, 'edit'])->name('share-preview.edit');
        Route::post('/{catalogPdf}/share-preview', [CatalogPdfSharePreviewController::class, 'update'])->name('share-preview.update');
        Route::post('/{catalogPdf}/workflow', [CatalogPdfController::class, 'selectWorkflow'])->name('workflow.select');
        Route::patch('/{catalogPdf}/publish', [CatalogPdfController::class, 'publish'])->name('publish');
        Route::patch('/{catalogPdf}/unpublish', [CatalogPdfController::class, 'unpublish'])->name('unpublish');
        Route::get('/{catalogPdf}/source', [CatalogPdfController::class, 'source'])->name('source');
        Route::delete('/{catalogPdf}', [CatalogPdfController::class, 'destroy'])->name('delete');

        Route::get('/{catalogPdf}/manage', [CatalogPdfPageManagementController::class, 'edit'])->name('manage');
        Route::get('/{catalogPdf}/preview', [CatalogPdfPageManagementController::class, 'preview'])->name('preview');
        Route::post('/{catalogPdf}/init-pages', [CatalogPdfPageManagementController::class, 'initPages'])->name('pages.init');
        Route::post('/{catalogPdf}/manage', [CatalogPdfPageManagementController::class, 'update'])->name('manage.update');
        Route::delete('/{catalogPdf}/pages/{page}', [CatalogPdfPageManagementController::class, 'destroyPage'])->name('pages.delete');
        Route::post('/{catalogPdf}/replace', [CatalogPdfPageManagementController::class, 'replacePdf'])->name('replace');

        Route::get('/{catalogPdf}/flip-physics', [CatalogPdfFlipPhysicsController::class, 'edit'])->name('flip-physics.edit');
        Route::post('/{catalogPdf}/flip-physics', [CatalogPdfFlipPhysicsController::class, 'update'])->name('flip-physics.update');
        Route::get('/{catalogPdf}/flip-physics/preview', [CatalogPdfFlipPhysicsController::class, 'preview'])->name('flip-physics.preview');

        Route::get('/{catalogPdf}/slicer', [CatalogPdfSlicerController::class, 'edit'])->name('slicer.edit');
        Route::post('/{catalogPdf}/slicer/pages/init', [CatalogPdfSlicerController::class, 'initPages'])->name('slicer.pages.init');
        Route::post('/{catalogPdf}/slicer/generate-images', [CatalogPdfSlicerController::class, 'generateImages'])->name('slicer.generate-images');
        Route::get('/{catalogPdf}/slicer/preview', [CatalogPdfSlicerController::class, 'preview'])->name('slicer.preview');
        Route::get('/{catalogPdf}/slicer/live', [CatalogPdfSlicerController::class, 'live'])->name('slicer.live');

        Route::get('/{catalogPdf}/slicer/pages/{page}/hotspots', [CatalogPdfSlicerController::class, 'hotspotsForPage'])->name('slicer.pages.hotspots');
        Route::post('/{catalogPdf}/slicer/pages/{page}/hotspots', [CatalogPdfSlicerController::class, 'storeHotspot'])->name('slicer.hotspots.store');
        Route::patch('/{catalogPdf}/slicer/hotspots/{hotspot}', [CatalogPdfSlicerController::class, 'updateHotspot'])->name('slicer.hotspots.update');
        Route::delete('/{catalogPdf}/slicer/hotspots/{hotspot}', [CatalogPdfSlicerController::class, 'destroyHotspot'])->name('slicer.hotspots.destroy');

        Route::post('/{catalogPdf}/slicer/track', [CatalogPdfSlicerController::class, 'track'])->name('slicer.track');
    });
});

Route::get('/error', function () {
    abort(500);
});

Route::get('/auth/redirect/{provider}', [SocialiteController::class, 'redirect']);

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->name('stripe.webhook');

require __DIR__ . '/auth.php';

<?php

use App\Http\Controllers\Apps\PermissionManagementController;
use App\Http\Controllers\Apps\RoleManagementController;
use App\Http\Controllers\Apps\UserManagementController;
use App\Http\Controllers\Apps\CatalogPdfController;
use App\Http\Controllers\Apps\CatalogPdfPageManagementController;
use App\Http\Controllers\Apps\CatalogPdfFlipPhysicsController;
use App\Http\Controllers\Apps\CatalogPdfSlicerController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Apps\CustomerTicketController;
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

    Route::name('user-management.')->group(function () {
        Route::resource('/user-management/users', UserManagementController::class);
        Route::resource('/user-management/roles', RoleManagementController::class);
        Route::resource('/user-management/permissions', PermissionManagementController::class);
    });

    // Customer Routes

    // Support Tickets
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [CustomerTicketController::class, 'index'])->name('index');
        Route::get('/create', [CustomerTicketController::class, 'create'])->name('create');
        Route::post('/', [CustomerTicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [CustomerTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [CustomerTicketController::class, 'reply'])->name('reply');
    });

    // Catalog PDFs
    Route::prefix('catalog/pdfs')->name('catalog.pdfs.')->group(function () {
        Route::get('/', [CatalogPdfController::class, 'index'])->name('index');
        Route::get('/create', [CatalogPdfController::class, 'create'])->name('create');
        Route::post('/', [CatalogPdfController::class, 'store'])->name('store');
        Route::get('/{catalogPdf}', [CatalogPdfController::class, 'show'])->name('show');
        Route::get('/{catalogPdf}/file', [CatalogPdfController::class, 'file'])->name('file');
        Route::get('/{catalogPdf}/source', [CatalogPdfController::class, 'source'])->name('source');
        Route::get('/{catalogPdf}/download', [CatalogPdfController::class, 'download'])->name('download');
            // Delete PDF
            Route::delete('/{catalogPdf}', [CatalogPdfController::class, 'destroy'])->name('delete');

        // Page Management template tools
        Route::get('/{catalogPdf}/manage', [CatalogPdfPageManagementController::class, 'edit'])->name('manage');
        Route::get('/{catalogPdf}/preview', [CatalogPdfPageManagementController::class, 'preview'])->name('preview');
        Route::get('/{catalogPdf}/share', [CatalogPdfPageManagementController::class, 'share'])->name('share');
        Route::post('/{catalogPdf}/init-pages', [CatalogPdfPageManagementController::class, 'initPages'])->name('pages.init');
        Route::post('/{catalogPdf}/manage', [CatalogPdfPageManagementController::class, 'update'])->name('manage.update');
        Route::delete('/{catalogPdf}/pages/{page}', [CatalogPdfPageManagementController::class, 'destroyPage'])->name('pages.delete');
        Route::post('/{catalogPdf}/replace', [CatalogPdfPageManagementController::class, 'replacePdf'])->name('replace');

        // Flip Physics template tools
        Route::get('/{catalogPdf}/flip-physics', [CatalogPdfFlipPhysicsController::class, 'edit'])->name('flip-physics.edit');
        Route::post('/{catalogPdf}/flip-physics', [CatalogPdfFlipPhysicsController::class, 'update'])->name('flip-physics.update');
        Route::get('/{catalogPdf}/flip-physics/preview', [CatalogPdfFlipPhysicsController::class, 'preview'])->name('flip-physics.preview');
        Route::get('/{catalogPdf}/flip-physics/share', [CatalogPdfFlipPhysicsController::class, 'share'])->name('flip-physics.share');

        // Slicer (Shoppable) template tools
        Route::get('/{catalogPdf}/slicer', [CatalogPdfSlicerController::class, 'edit'])->name('slicer.edit');
        Route::post('/{catalogPdf}/slicer/pages/init', [CatalogPdfSlicerController::class, 'initPages'])->name('slicer.pages.init');
        Route::post('/{catalogPdf}/slicer/generate-images', [CatalogPdfSlicerController::class, 'generateImages'])->name('slicer.generate-images');
        Route::get('/{catalogPdf}/slicer/preview', [CatalogPdfSlicerController::class, 'preview'])->name('slicer.preview');
        Route::get('/{catalogPdf}/slicer/live', [CatalogPdfSlicerController::class, 'live'])->name('slicer.live');

        Route::get('/{catalogPdf}/slicer/pages/{page}/image', [CatalogPdfSlicerController::class, 'pageImage'])->name('slicer.pages.image');
        Route::get('/{catalogPdf}/slicer/pages/{page}/hotspots', [CatalogPdfSlicerController::class, 'hotspotsForPage'])->name('slicer.pages.hotspots');
        Route::post('/{catalogPdf}/slicer/pages/{page}/hotspots', [CatalogPdfSlicerController::class, 'storeHotspot'])->name('slicer.hotspots.store');
        Route::patch('/{catalogPdf}/slicer/hotspots/{hotspot}', [CatalogPdfSlicerController::class, 'updateHotspot'])->name('slicer.hotspots.update');
        Route::delete('/{catalogPdf}/slicer/hotspots/{hotspot}', [CatalogPdfSlicerController::class, 'destroyHotspot'])->name('slicer.hotspots.destroy');
        Route::get('/{catalogPdf}/slicer/hotspots/{hotspot}/media/{kind}', [CatalogPdfSlicerController::class, 'hotspotMedia'])->name('slicer.hotspots.media');

        Route::post('/{catalogPdf}/slicer/track', [CatalogPdfSlicerController::class, 'track'])->name('slicer.track');
    });
}); 

Route::get('/error', function () {
    abort(500);
});

Route::get('/auth/redirect/{provider}', [SocialiteController::class, 'redirect']);

require __DIR__ . '/auth.php';

<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\ApprovalsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PublicDocumentController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing');
})->name('landing');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:admin,employee', 'employee.page'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Documents
    Route::get('/documents',                      [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/upload',               [DocumentController::class, 'uploadForm'])->name('documents.upload');
    Route::get('/documents/returned',             [DocumentController::class, 'returnedIndex'])->name('documents.returned');
    Route::post('/documents',                     [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/edit',      [DocumentController::class, 'edit'])->name('documents.edit');
    Route::put('/documents/{document}',           [DocumentController::class, 'update'])->name('documents.update');
    Route::get('/documents/{document}',           [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/file',      [DocumentController::class, 'file'])->name('documents.file');

    // Visitors
    Route::get('/visitors',  [VisitorController::class, 'index'])->name('visitors.index');
    Route::post('/visitors', [VisitorController::class, 'store'])->name('visitors.store');

    // Certificates
    Route::get('/certificates',                        [CertificateController::class, 'index'])->name('certificates.index');
    Route::post('/certificates',                       [CertificateController::class, 'generate'])->name('certificates.generate');
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');

    // Global search (top-bar suggest only)
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');

    // Archive
    Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');

    // Workflow (visible to admin + employees; per-action rules are enforced in the controller)
    Route::get('/workflow',                     [WorkflowController::class, 'index'])->name('workflow.index');
    Route::put('/workflow/{document}/advance',  [WorkflowController::class, 'advance'])->name('workflow.advance');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    // Users
    Route::get('/users',              [UserController::class, 'index'])->name('users.index');
    Route::post('/users',             [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}',       [UserController::class, 'update'])->name('users.update');

    // Departments
    Route::get('/departments',                 [DepartmentController::class, 'index'])->name('departments.index');
    Route::post('/departments',                [DepartmentController::class, 'store'])->name('departments.store');
    Route::put('/departments/{department}',    [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

    // Approvals
    Route::get('/approvals',                             [ApprovalsController::class, 'index'])->name('approvals.index');
    Route::put('/approvals/{document}/approve',          [ApprovalsController::class, 'approve'])->name('approvals.approve');
    Route::put('/approvals/{document}/return',           [ApprovalsController::class, 'returnDoc'])->name('approvals.return');
    Route::put('/approvals/{document}/reject',           [ApprovalsController::class, 'reject'])->name('approvals.reject');

    // Request Reviews
    Route::get('/requests',                              [RequestController::class, 'adminIndex'])->name('requests.index');
    Route::put('/requests/{documentRequest}/status',     [RequestController::class, 'adminUpdateStatus'])->name('requests.update-status');
    Route::post('/requests/{documentRequest}/complete', [RequestController::class, 'adminComplete'])->name('requests.complete');

    // Archive
    Route::put('/archive/{document}/restore', [ArchiveController::class, 'restore'])->name('archive.restore');

    // Reports
    Route::get('/reports', function () {
        return Inertia::render('Reports/Index');
    })->name('reports.index');

    // Audit
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

// Guest-facing pages
Route::get('/get-documents', [PublicDocumentController::class, 'index'])->name('get-documents.index');
Route::get('/get-documents/{document}', [PublicDocumentController::class, 'show'])->name('get-documents.show');
Route::get('/get-documents/{document}/file', [PublicDocumentController::class, 'file'])->name('get-documents.file');
Route::get('/requests/new',    [RequestController::class, 'create'])->name('requests.create');
Route::post('/requests',       [RequestController::class, 'store'])->name('requests.store');
Route::get('/requests/status', [RequestController::class, 'status'])->name('requests.status');
Route::get('/requests/{documentRequest}/download', [RequestController::class, 'downloadResponse'])->name('requests.download');

// Public certificate verification (QR scan)
Route::get('/certificates/verify/{token}', [CertificateController::class, 'verify'])->name('certificates.verify');

// Guest COA flow
Route::get('/requests/{documentRequest}/certificate',  [RequestController::class, 'showCertificateForm'])->name('requests.certificate.form');
Route::post('/requests/{documentRequest}/certificate', [RequestController::class, 'issueGuestCertificate'])->name('requests.certificate.issue');
Route::get('/guest/certificates/{certificate}',         [RequestController::class, 'guestCertificatePreview'])->name('guest.certificate.preview');
Route::get('/guest/certificates/{certificate}/download',[RequestController::class, 'guestCertificateDownload'])->name('guest.certificate.download');

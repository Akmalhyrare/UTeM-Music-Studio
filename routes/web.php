<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\StaffBookingController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\StaffBorrowingController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\StudioManagementController;
use App\Http\Controllers\StudioImageController;
use App\Http\Controllers\StudioUnavailabilityController;
use App\Http\Controllers\ItemImageController;
use App\Http\Controllers\SettingsController;

// ── PUBLIC ROUTES ─────────────────────────────
Route::get('/',       [LandingController::class, 'index'])->name('landing');
Route::get('/items',  [BrowseController::class,  'items'])->name('items.browse');
Route::get('/studios',[BrowseController::class,  'studios'])->name('studios.browse');
Route::get('/studios/{studio}', [BrowseController::class, 'studio'])->name('studios.show');

// ── AUTH ROUTES (guest only) ──────────────────
Route::get('/login',   [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',  [AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post')->middleware('throttle:10,1');

// ── STAFF ROUTES ──────────────────────────────
Route::middleware('auth.staff')->group(function () {
    Route::get('/staff/dashboard', [StaffController::class, 'dashboard'])->name('staff.dashboard');

    // Account settings
    Route::get('/staff/settings',  [SettingsController::class, 'index'])->name('staff.settings');
    Route::put('/staff/settings/profile', [SettingsController::class, 'updateProfile'])->name('staff.settings.profile');
    Route::put('/staff/settings/password', [SettingsController::class, 'updatePassword'])->name('staff.settings.password');
    Route::post('/staff/settings/logout-other-sessions', [SettingsController::class, 'logoutOtherSessions'])->name('staff.settings.logout-others');

    // Inventory — specific paths must come before {id} wildcard
    Route::get('/inventory/categories',       [InventoryController::class, 'categories'])->name('inventory.categories');
    Route::post('/inventory/categories',      [InventoryController::class, 'storeCategory'])->name('inventory.categories.store');
    Route::delete('/inventory/categories/{id}', [InventoryController::class, 'destroyCategory'])->name('inventory.categories.destroy');

    Route::get('/inventory',          [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/create',   [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/inventory',         [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{id}/edit',[InventoryController::class, 'edit'])->name('inventory.edit');
    Route::put('/inventory/{id}',     [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{id}',  [InventoryController::class, 'destroy'])->name('inventory.destroy');

    // Inventory item images
    Route::post('/inventory/{id}/images',         [ItemImageController::class, 'store'])->name('inventory.images.store');
    Route::post('/inventory/{id}/images/reorder', [ItemImageController::class, 'reorder'])->name('inventory.images.reorder');
    Route::put('/inventory/{id}/images/{imageId}/primary', [ItemImageController::class, 'setPrimary'])->name('inventory.images.primary');

    // Bookings — staff management
    Route::get('/staff/bookings',          [StaffBookingController::class, 'index'])->name('staff.bookings.index');
    Route::get('/staff/bookings/{id}',     [StaffBookingController::class, 'show'])->name('staff.bookings.show');
    Route::put('/staff/bookings/{id}',     [StaffBookingController::class, 'update'])->name('staff.bookings.update');
    Route::delete('/staff/bookings/{id}',  [StaffBookingController::class, 'destroy'])->name('staff.bookings.destroy');

    // Borrowings — staff management
    Route::get('/staff/borrowings',           [StaffBorrowingController::class, 'index'])->name('staff.borrowings.index');
    Route::get('/staff/borrowings/{id}',      [StaffBorrowingController::class, 'show'])->name('staff.borrowings.show');
    Route::put('/staff/borrowings/{id}/approve', [StaffBorrowingController::class, 'approve'])->name('staff.borrowings.approve');
    Route::put('/staff/borrowings/{id}/reject',  [StaffBorrowingController::class, 'reject'])->name('staff.borrowings.reject');
    Route::put('/staff/borrowings/{id}/collect', [StaffBorrowingController::class, 'collect'])->name('staff.borrowings.collect');
    Route::post('/staff/borrowings/{id}/return', [StaffBorrowingController::class, 'processReturn'])->name('staff.borrowings.return');

    // Maintenance — staff management
    Route::get('/staff/maintenance',          [MaintenanceController::class, 'index'])->name('staff.maintenance.index');
    Route::put('/staff/maintenance/{id}/resolve', [MaintenanceController::class, 'resolve'])->name('staff.maintenance.resolve');

    // Global search
    Route::get('/staff/search', [GlobalSearchController::class, 'index'])->name('staff.search');

    // Studio Management — specific paths must come before {id} wildcard
    Route::get('/staff/studios',        [StudioManagementController::class, 'index'])->name('staff.studios.index');
    Route::get('/staff/studios/create', [StudioManagementController::class, 'create'])->name('staff.studios.create');
    Route::post('/staff/studios',       [StudioManagementController::class, 'store'])->name('staff.studios.store');
    Route::get('/staff/studios/{id}/edit', [StudioManagementController::class, 'edit'])->name('staff.studios.edit');
    Route::put('/staff/studios/{id}',   [StudioManagementController::class, 'update'])->name('staff.studios.update');
    Route::get('/staff/studios/{id}',   [StudioManagementController::class, 'show'])->name('staff.studios.show');

    // Studio images
    Route::post('/staff/studios/{id}/images',         [StudioImageController::class, 'store'])->name('staff.studios.images.store');
    Route::post('/staff/studios/{id}/images/reorder', [StudioImageController::class, 'reorder'])->name('staff.studios.images.reorder');
    Route::put('/staff/studios/{id}/images/{imageId}/primary', [StudioImageController::class, 'setPrimary'])->name('staff.studios.images.primary');

    // Studio calendar / unavailability periods
    Route::get('/staff/studios/{id}/calendar',      [StudioUnavailabilityController::class, 'calendar'])->name('staff.studios.calendar');
    Route::get('/staff/studios/{id}/calendar-data', [StudioUnavailabilityController::class, 'calendarData'])->name('staff.studios.calendar.data');
    Route::post('/staff/studios/{id}/unavailability', [StudioUnavailabilityController::class, 'store'])->name('staff.studios.unavailability.store');
    Route::delete('/staff/studios/{id}/unavailability/{periodId}', [StudioUnavailabilityController::class, 'destroy'])->name('staff.studios.unavailability.destroy');
});

// ── STUDENT ROUTES ────────────────────────────
Route::middleware('auth.student')->group(function () {
    Route::get('/student/dashboard', [StudentController::class, 'dashboard'])->name('student.dashboard');

    // Account settings
    Route::get('/student/settings',  [SettingsController::class, 'index'])->name('student.settings');
    Route::put('/student/settings/profile', [SettingsController::class, 'updateProfile'])->name('student.settings.profile');
    Route::put('/student/settings/password', [SettingsController::class, 'updatePassword'])->name('student.settings.password');
    Route::post('/student/settings/logout-other-sessions', [SettingsController::class, 'logoutOtherSessions'])->name('student.settings.logout-others');

    // Bookings — specific paths must come before {id} wildcard
    Route::get('/bookings/create',  [BookingController::class, 'create'])->name('bookings.create');
    Route::get('/studios/{studio}/availability', [BookingController::class, 'availability'])->name('studios.availability');
    Route::get('/bookings',         [BookingController::class, 'index'])->name('bookings.index');
    Route::post('/bookings',        [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{id}/edit',[BookingController::class, 'edit'])->name('bookings.edit');
    Route::get('/bookings/{id}',    [BookingController::class, 'show'])->name('bookings.show');
    Route::put('/bookings/{id}',    [BookingController::class, 'update'])->name('bookings.update');
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy'])->name('bookings.destroy');

    // Borrowings — specific paths must come before {id} wildcard
    Route::get('/borrowings/create',  [BorrowingController::class, 'create'])->name('borrowings.create');
    Route::get('/borrowings/availability', [BorrowingController::class, 'availability'])->name('borrowings.availability');
    Route::get('/borrowings',         [BorrowingController::class, 'index'])->name('borrowings.index');
    Route::post('/borrowings',        [BorrowingController::class, 'store'])->name('borrowings.store');
    Route::get('/borrowings/{id}',    [BorrowingController::class, 'show'])->name('borrowings.show');
    Route::delete('/borrowings/{id}', [BorrowingController::class, 'destroy'])->name('borrowings.destroy');

    // Global search
    Route::get('/search', [GlobalSearchController::class, 'index'])->name('student.search');
});

// ── ADMIN-ONLY ROUTES ─────────────────────────
Route::middleware('auth.admin')->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Reports — admin only (CRITICAL: must never be reachable by staff)
    Route::get('/admin/reports', [ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/admin/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('admin.reports.export.pdf');
    Route::get('/admin/reports/export/csv', [ReportController::class, 'exportCsv'])->name('admin.reports.export.csv');

    Route::get('/admin/users', [UserManagementController::class, 'index'])->name('users.index');

    // Staff accounts
    Route::get('/admin/users/staff/create',    [UserManagementController::class, 'createStaff'])->name('users.staff.create');
    Route::post('/admin/users/staff',          [UserManagementController::class, 'storeStaff'])->name('users.staff.store');
    Route::get('/admin/users/staff/{id}/edit', [UserManagementController::class, 'editStaff'])->name('users.staff.edit');
    Route::put('/admin/users/staff/{id}',      [UserManagementController::class, 'updateStaff'])->name('users.staff.update');
    Route::delete('/admin/users/staff/{id}',   [UserManagementController::class, 'destroyStaff'])->name('users.staff.destroy');

    // Student accounts
    Route::get('/admin/users/student/{id}/edit', [UserManagementController::class, 'editStudent'])->name('users.student.edit');
    Route::put('/admin/users/student/{id}',      [UserManagementController::class, 'updateStudent'])->name('users.student.update');
    Route::delete('/admin/users/student/{id}',   [UserManagementController::class, 'destroyStudent'])->name('users.student.destroy');

    // Studio Management — destructive actions
    Route::delete('/staff/studios/{id}', [StudioManagementController::class, 'archive'])->name('staff.studios.archive');
    Route::delete('/staff/studios/{id}/images/{imageId}', [StudioImageController::class, 'destroy'])->name('staff.studios.images.destroy');

    // Inventory item images — destructive actions
    Route::delete('/inventory/{id}/images/{imageId}', [ItemImageController::class, 'destroy'])->name('inventory.images.destroy');
});

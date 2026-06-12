<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\BrowseController;

// ── PUBLIC ROUTES ─────────────────────────────
Route::get('/',       [LandingController::class, 'index'])->name('landing');
Route::get('/items',  [BrowseController::class,  'items'])->name('items.browse');
Route::get('/studios',[BrowseController::class,  'studios'])->name('studios.browse');

// ── AUTH ROUTES (guest only) ──────────────────
Route::get('/login',   [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',  [AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

// ── STAFF ROUTES ──────────────────────────────
Route::middleware('auth.staff')->group(function () {
    Route::get('/staff/dashboard', [StaffController::class, 'dashboard'])->name('staff.dashboard');

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
});

// ── STUDENT ROUTES ────────────────────────────
Route::middleware('auth.student')->group(function () {
    Route::get('/student/dashboard', [StudentController::class, 'dashboard'])->name('student.dashboard');
});

// ── ADMIN-ONLY ROUTES ─────────────────────────
Route::middleware('auth.admin')->group(function () {
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
});

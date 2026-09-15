<?php

use App\Modules\Web\Http\Controllers\Auth\AdminAuthController;
use App\Modules\Web\Http\Controllers\DashboardController;
use App\Modules\Web\Http\Controllers\Features\FeatureController;
use App\Modules\Web\Http\Controllers\Payments\PaymentController;
use App\Modules\Web\Http\Controllers\Plans\PlanController;
use App\Modules\Web\Http\Controllers\ProfileController;
use App\Modules\Web\Http\Controllers\Subscriptions\SubscriptionController;
use App\Modules\Web\Http\Controllers\Tenants\TenantController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AdminAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AdminAuthController::class, 'register']);
});

// Authenticated Admin Dashboard routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // Profile & Security
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Tenants management
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
    Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');
    Route::post('/tenants/{tenant}/toggle-status', [TenantController::class, 'toggleStatus'])->name('tenants.toggle-status');

    // Plans & Pricing
    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/create', [PlanController::class, 'create'])->name('plans.create');
    Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/{plan}', [PlanController::class, 'show'])->name('plans.show');
    Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
    Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
    Route::get('/plans/{plan}/features', [PlanController::class, 'features'])->name('plans.features');
    Route::put('/plans/{plan}/features', [PlanController::class, 'updateFeatures'])->name('plans.features.update');
    Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');
    Route::get('/plans-matrix', [PlanController::class, 'matrix'])->name('plans.matrix');

    // Features & Limits
    Route::get('/features', [FeatureController::class, 'index'])->name('features.index');
    Route::get('/features/create', [FeatureController::class, 'create'])->name('features.create');
    Route::post('/features', [FeatureController::class, 'store'])->name('features.store');
    Route::get('/features/{feature}/edit', [FeatureController::class, 'edit'])->name('features.edit');
    Route::put('/features/{feature}', [FeatureController::class, 'update'])->name('features.update');
    Route::delete('/features/{feature}', [FeatureController::class, 'destroy'])->name('features.destroy');

    // Subscriptions
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');

    // Payments & Invoices
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
});

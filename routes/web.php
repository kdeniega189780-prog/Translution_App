<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1')->name('login.store');
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
    Route::view('/forgot-password', 'auth.forgot')->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', fn (string $token) => view('auth.reset', compact('token')))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::middleware('role:admin,secretary,mechanic,purchaser,customer')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::post('/notifications/read', [ProfileController::class, 'notifications'])->name('notifications.read');
    });
    Route::middleware('role:admin,secretary,customer')->group(function () {
        Route::resource('bookings', BookingController::class)->only(['create', 'store']);
        Route::post('/bookings/{booking}/consent', [BookingController::class, 'consent'])->name('bookings.consent');
        Route::resource('vehicles', VehicleController::class)->except('destroy');
        Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);
    });
    Route::middleware('role:admin,secretary,mechanic,customer')->group(function () {
        Route::resource('bookings', BookingController::class)->only(['index', 'show']);
    });
    Route::middleware('role:admin,secretary,mechanic')->group(function () {
        Route::get('/repair-jobs', [BookingController::class, 'index'])->name('jobs.index');
        Route::post('/bookings/{booking}/advance', [BookingController::class, 'advance'])->name('bookings.advance');
    });
    Route::middleware('role:admin,mechanic')->group(function () {
        Route::post('/bookings/{booking}/diagnosis', [BookingController::class, 'diagnose'])->name('bookings.diagnose');
        Route::post('/bookings/{booking}/parts', [BookingController::class, 'usePart'])->name('bookings.parts');
    });
    Route::middleware('role:admin,secretary')->group(function () {
        Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::resource('customers', CustomerController::class)->except(['show', 'destroy']);
    });
    Route::middleware('role:admin,purchaser')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::post('/orders/{order}/advance', [OrderController::class, 'advance'])->name('orders.advance');
    });
    Route::middleware('role:admin')->group(function () {
        Route::post('/bookings/{booking}/reverse', [BookingController::class, 'reverse'])->name('bookings.reverse');
        Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::resource('services', ServiceController::class)->except(['show', 'destroy']);
        Route::get('/inventory/create', [InventoryController::class, 'create'])->name('inventory.create');
        Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
        Route::get('/inventory/{part}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
        Route::put('/inventory/{part}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::post('/users/{user}/reset-password', [UserController::class, 'reset'])->name('users.reset');
    });
});

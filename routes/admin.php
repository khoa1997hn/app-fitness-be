<?php

use App\Admin\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "web" middleware group. Enjoy building your admin!
|
*/

// Auth routes (guest only)
Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth:admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    Route::resource('users', \App\Admin\Http\Controllers\UserController::class)->only(['index', 'destroy'])->names(['index' => 'users.index', 'destroy' => 'users.destroy']);
    Route::get('users/export', [\App\Admin\Http\Controllers\UserController::class, 'export'])->name('users.export');
    Route::resource('tours', \App\Admin\Http\Controllers\TourController::class)->names(['index' => 'tours.index', 'create' => 'tours.create', 'store' => 'tours.store', 'edit' => 'tours.edit', 'update' => 'tours.update', 'destroy' => 'tours.destroy']);
    Route::resource('tracking-emails', \App\Admin\Http\Controllers\TrackingEmailController::class)->only(['index', 'destroy'])->names(['index' => 'tracking-emails.index', 'destroy' => 'tracking-emails.destroy']);
    Route::get('tracking-emails/export', [\App\Admin\Http\Controllers\TrackingEmailController::class, 'export'])->name('tracking-emails.export');
    Route::resource('coupons', \App\Admin\Http\Controllers\CouponController::class)->names(['index' => 'coupons.index', 'create' => 'coupons.create', 'store' => 'coupons.store', 'edit' => 'coupons.edit', 'update' => 'coupons.update', 'destroy' => 'coupons.destroy']);
    Route::get('evisa', [\App\Admin\Http\Controllers\EVisaController::class, 'index'])->name('evisa.index');
    Route::post('evisa', [\App\Admin\Http\Controllers\EVisaController::class, 'store'])->name('evisa.store');
    Route::get('fasttrack', [\App\Admin\Http\Controllers\FastTrackController::class, 'index'])->name('fasttrack.index');
    Route::post('fasttrack', [\App\Admin\Http\Controllers\FastTrackController::class, 'store'])->name('fasttrack.store');
    Route::get('carpickup', [\App\Admin\Http\Controllers\CarPickupController::class, 'index'])->name('carpickup.index');
    Route::post('carpickup', [\App\Admin\Http\Controllers\CarPickupController::class, 'store'])->name('carpickup.store');
    Route::get('esim', [\App\Admin\Http\Controllers\ESimController::class, 'index'])->name('esim.index');
    Route::post('esim', [\App\Admin\Http\Controllers\ESimController::class, 'store'])->name('esim.store');
});

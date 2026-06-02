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
    Route::post('/files/presigned-upload', [\App\Admin\Http\Controllers\FileController::class, 'presignedUpload'])->name('files.presigned-upload');
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    Route::get('users/export', [\App\Admin\Http\Controllers\UserController::class, 'export'])->name('users.export');
    Route::resource('users', \App\Admin\Http\Controllers\UserController::class)->only(['index', 'show', 'destroy'])->names(['index' => 'users.index', 'show' => 'users.show', 'destroy' => 'users.destroy']);
    Route::resource('banners', \App\Admin\Http\Controllers\BannerController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('programs', \App\Admin\Http\Controllers\ProgramController::class)->only(['index', 'edit', 'update', 'destroy']);
    // scoped(): ràng buộc lesson PHẢI thuộc program trên URL (chống IDOR — sửa/xóa lesson của program khác qua manipulate ID).
    Route::resource('programs.lessons', \App\Admin\Http\Controllers\LessonController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])->scoped();
});

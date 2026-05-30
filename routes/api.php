<?php

use App\Web\Http\Controllers\API\V1\Auth\AuthController;
use App\Web\Http\Controllers\API\V1\Auth\ProfileController;
use App\Web\Http\Controllers\API\V1\Auth\RegistrationController;
use App\Web\Http\Controllers\API\V1\BannerController;
use App\Web\Http\Controllers\API\V1\LessonFavoriteController;
use App\Web\Http\Controllers\API\V1\ProgramController;
use App\Web\Http\Controllers\API\V1\ProgramSelectionController;
use App\Web\Http\Controllers\API\V1\Subscription\AppleIapController;
use App\Web\Http\Controllers\API\V1\Subscription\GoogleIapController;
use App\Web\Http\Controllers\API\V1\Subscription\SubscriptionCancelController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::as('api.')
    ->middleware([\App\Share\Http\Middleware\SetLocaleMiddleware::class])
    ->group(function () {
        Route::get('/health', function () {
            return response()->json([
                'status' => 'ok',
                'message' => 'API is running',
            ]);
        })->name('health');

        Route::prefix('v1')->group(function () {
            Route::as('auth.')
                ->prefix('auth')
                ->group(function () {
                    Route::post('register', [RegistrationController::class, 'register'])->name('register');
                    Route::post('login', [AuthController::class, 'login'])->name('login');

                    Route::middleware('auth:api')->group(function () {
                        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
                        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
                        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
                        Route::delete('me', [ProfileController::class, 'destroy'])->name('me.destroy');
                    });
                });

            Route::as('subscriptions.')
                ->prefix('subscriptions')
                ->middleware('auth:api')
                ->group(function () {
                    Route::post('iap/google/verify', [GoogleIapController::class, 'verify'])
                        ->name('iap.google.verify');
                    Route::post('iap/apple/verify', [AppleIapController::class, 'verify'])
                        ->name('iap.apple.verify');
                    Route::post('cancel', [SubscriptionCancelController::class, 'store'])
                        ->name('cancel');
                });

            Route::get('banners', [BannerController::class, 'index'])->name('banners.index');

            Route::middleware('auth:api')->group(function () {
                Route::get('programs', [ProgramController::class, 'index'])->name('programs.index');
                Route::get('programs/purchased', [ProgramSelectionController::class, 'purchased'])->name('programs.purchased');
                Route::get('programs/selection', [ProgramSelectionController::class, 'show'])->name('programs.selection.show');
                Route::post('programs/selection', [ProgramSelectionController::class, 'store'])->name('programs.selection.store');
                Route::get('programs/{program}', [ProgramController::class, 'show'])->name('programs.show');

                Route::get('lessons/favorites', [LessonFavoriteController::class, 'index'])->name('lessons.favorites.index');
                Route::post('lessons/{lesson}/favorite', [LessonFavoriteController::class, 'store'])->name('lessons.favorite.store');
                Route::delete('lessons/{lesson}/favorite', [LessonFavoriteController::class, 'destroy'])->name('lessons.favorite.destroy');
            });
        });
    });

<?php

use App\Web\Http\Controllers\API\V1\Auth\AuthController;
use App\Web\Http\Controllers\API\V1\Auth\RegistrationController;
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
                        Route::get('me', [AuthController::class, 'me'])->name('me');
                    });
                });
        });
    });

<?php

use App\Web\Http\Controllers\API\V1\Auth\AuthController;
use App\Web\Http\Controllers\API\V1\Auth\PasswordResetController;
use App\Web\Http\Controllers\API\V1\Auth\ProfileController;
use App\Web\Http\Controllers\API\V1\Auth\RegistrationController;
use App\Web\Http\Controllers\API\V1\BannerController;
use App\Web\Http\Controllers\API\V1\ComboController;
use App\Web\Http\Controllers\API\V1\Gym\AccountController;
use App\Web\Http\Controllers\API\V1\Gym\Admin\AttendanceController as AdminAttendanceController;
use App\Web\Http\Controllers\API\V1\Gym\Admin\BookingController as AdminBookingController;
use App\Web\Http\Controllers\API\V1\Gym\Admin\BookingRequestController as AdminBookingRequestController;
use App\Web\Http\Controllers\API\V1\Gym\Admin\MemberController as AdminMemberController;
use App\Web\Http\Controllers\API\V1\Gym\Admin\OrderController as AdminOrderController;
use App\Web\Http\Controllers\API\V1\Gym\Admin\PackageController as AdminPackageController;
use App\Web\Http\Controllers\API\V1\Gym\Admin\ScheduleCancelRequestController as AdminScheduleCancelRequestController;
use App\Web\Http\Controllers\API\V1\Gym\Admin\TeachingScheduleController as AdminTeachingScheduleController;
use App\Web\Http\Controllers\API\V1\Gym\Admin\TimetableController as AdminTimetableController;
use App\Web\Http\Controllers\API\V1\Gym\Admin\UserController as AdminUserController;
use App\Web\Http\Controllers\API\V1\Gym\BookingController;
use App\Web\Http\Controllers\API\V1\Gym\BookingRequestController;
use App\Web\Http\Controllers\API\V1\Gym\NotificationController;
use App\Web\Http\Controllers\API\V1\Gym\OrderController;
use App\Web\Http\Controllers\API\V1\Gym\OwnedItemController;
use App\Web\Http\Controllers\API\V1\Gym\PackageController;
use App\Web\Http\Controllers\API\V1\Gym\TherapyServiceController;
use App\Web\Http\Controllers\API\V1\LessonController;
use App\Web\Http\Controllers\API\V1\LessonFavoriteController;
use App\Web\Http\Controllers\API\V1\ProgramController;
use App\Web\Http\Controllers\API\V1\ProgramFavoriteController;
use App\Web\Http\Controllers\API\V1\ProgramLeftOffController;
use App\Web\Http\Controllers\API\V1\ProgramSelectionController;
use App\Web\Http\Controllers\API\V1\Subscription\AppleIapController;
use App\Web\Http\Controllers\API\V1\Subscription\GoogleIapController;
use App\Web\Http\Controllers\API\V1\Subscription\SubscriptionController;
use App\Web\Http\Controllers\API\V1\VideoPlayController;
use App\Web\Http\Controllers\API\V1\VideoWatchProgressController;
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
                    Route::post('password/reset', [PasswordResetController::class, 'reset'])->name('password.reset');

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
                    Route::get('me', [SubscriptionController::class, 'show'])
                        ->name('me');
                    Route::post('cancel', [SubscriptionController::class, 'cancel'])
                        ->name('cancel');
                });

            Route::get('banners', [BannerController::class, 'index'])->name('banners.index');

            // Hệ thống phòng tập offline (fitness-web)
            Route::get('packages', [PackageController::class, 'index'])->name('packages.index');
            Route::get('packages/{package}', [PackageController::class, 'show'])->name('packages.show');
            Route::get('therapy-services', [TherapyServiceController::class, 'index'])->name('therapy-services.index');
            Route::get('therapy-services/{therapyService:slug}', [TherapyServiceController::class, 'show'])->name('therapy-services.show');

            Route::patch('users/me/password', [AccountController::class, 'changePassword'])
                ->middleware('auth:api')
                ->name('users.me.password');

            Route::middleware(['auth:api', 'permission:student.portal'])->group(function () {
                Route::patch('users/me', [AccountController::class, 'update'])->name('users.me.update');
                Route::get('users/me/notifications', [NotificationController::class, 'index'])->name('users.me.notifications');
                Route::patch('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
                Route::get('users/me/orders', [OrderController::class, 'index'])->name('users.me.orders');
                Route::get('users/me/packages', [OwnedItemController::class, 'packages'])->name('users.me.packages');
                Route::get('users/me/therapies', [OwnedItemController::class, 'therapies'])->name('users.me.therapies');
                Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
                Route::get('orders/{order:code}', [OrderController::class, 'show'])->name('orders.show');
                Route::post('orders/{order:code}/accept-terms', [OrderController::class, 'acceptTerms'])->name('orders.accept-terms');
                Route::post('payments', [OrderController::class, 'pay'])->name('payments.store');

                Route::get('schedules', [BookingController::class, 'schedules'])->name('schedules.index');
                Route::get('schedules/{schedule}', [BookingController::class, 'schedule'])->name('schedules.show');
                Route::get('users/me/bookings', [BookingController::class, 'index'])->name('users.me.bookings');
                Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
                Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

                Route::get('users/me/extra-people', [BookingRequestController::class, 'extraPeople'])->name('users.me.extra-people');
                Route::get('users/me/private-requests', [BookingRequestController::class, 'privateRequests'])->name('users.me.private-requests');
                Route::post('private-requests', [BookingRequestController::class, 'storePrivate'])->name('private-requests.store');
                Route::patch('private-requests/{privateRequest}/cancel', [BookingRequestController::class, 'cancelPrivate'])->name('private-requests.cancel');
                Route::get('users/me/therapy-requests', [BookingRequestController::class, 'therapyRequests'])->name('users.me.therapy-requests');
                Route::post('therapy-requests', [BookingRequestController::class, 'storeTherapy'])->name('therapy-requests.store');
                Route::patch('therapy-requests/{therapyRequest}/cancel', [BookingRequestController::class, 'cancelTherapy'])->name('therapy-requests.cancel');
            });

            Route::as('admin.')
                ->prefix('admin')
                ->middleware('auth:api')
                ->group(function () {
                    Route::middleware('permission:packages.manage')->group(function () {
                        Route::patch('packages/{package}/status', [AdminPackageController::class, 'updateStatus'])->name('packages.status');
                        Route::apiResource('packages', AdminPackageController::class);
                    });

                    Route::middleware('permission:users.manage')->group(function () {
                        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
                        Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
                        Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
                        Route::patch('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
                        Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');
                        Route::post('users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
                        Route::get('members', [AdminMemberController::class, 'index'])->name('members.index');
                        Route::post('members', [AdminMemberController::class, 'store'])->name('members.store');
                        Route::get('members/{user}', [AdminMemberController::class, 'show'])->name('members.show');
                    });

                    Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole'])
                        ->middleware('permission:users.change_role')
                        ->name('users.role');
                    Route::delete('users/{user}', [AdminUserController::class, 'destroy'])
                        ->middleware('permission:records.delete')
                        ->name('users.destroy');
                    Route::middleware('permission:users.extend_package')->group(function () {
                        Route::patch('users/{user}/packages/{userPackage}/extend', [AdminUserController::class, 'extendPackage'])->name('users.packages.extend');
                        Route::patch('users/{user}/therapies/{userTherapy}/extend', [AdminUserController::class, 'extendTherapy'])->name('users.therapies.extend');
                    });

                    Route::post('users/{user}/orders', [AdminOrderController::class, 'store'])
                        ->middleware('permission:orders.create')
                        ->name('users.orders.store');

                    Route::middleware('permission:payments.manage')->group(function () {
                        Route::get('payments', [AdminOrderController::class, 'index'])->name('payments.index');
                        Route::get('payments/{order}', [AdminOrderController::class, 'show'])->name('payments.show');
                    });

                    Route::middleware('permission:schedules.timetable.manage')->group(function () {
                        Route::get('timetable/options', [AdminTimetableController::class, 'options'])->name('timetable.options');
                        Route::get('timetable', [AdminTimetableController::class, 'index'])->name('timetable.index');
                        Route::post('timetable', [AdminTimetableController::class, 'store'])->name('timetable.store');
                        Route::get('timetable/{schedule}', [AdminTimetableController::class, 'show'])->name('timetable.show');
                        Route::patch('timetable/{schedule}', [AdminTimetableController::class, 'update'])->name('timetable.update');
                        Route::patch('timetable/{schedule}/cancel', [AdminTimetableController::class, 'cancel'])->name('timetable.cancel');
                    });

                    Route::middleware('permission:schedules.teaching.view')->group(function () {
                        Route::get('schedules', [AdminTeachingScheduleController::class, 'index'])->name('schedules.index');
                        Route::get('schedules/{type}/{id}', [AdminTeachingScheduleController::class, 'show'])
                            ->whereIn('type', ['class', 'private'])
                            ->whereNumber('id')
                            ->name('schedules.show');
                    });
                    Route::patch('schedules/{type}/{id}/cancel', [AdminTeachingScheduleController::class, 'cancel'])
                        ->middleware('permission:schedules.teaching.cancel')
                        ->whereIn('type', ['class', 'private'])
                        ->whereNumber('id')
                        ->name('schedules.cancel');

                    Route::middleware('permission:schedules.cancel_request.review')->group(function () {
                        Route::get('schedule-cancel-requests', [AdminScheduleCancelRequestController::class, 'index'])->name('schedule-cancel-requests.index');
                        Route::patch('schedule-cancel-requests/{scheduleCancelRequest}/approve', [AdminScheduleCancelRequestController::class, 'approve'])->name('schedule-cancel-requests.approve');
                        Route::patch('schedule-cancel-requests/{scheduleCancelRequest}/reject', [AdminScheduleCancelRequestController::class, 'reject'])->name('schedule-cancel-requests.reject');
                    });

                    Route::get('attendance/sessions', [AdminAttendanceController::class, 'sessions'])
                        ->middleware('permission:attendance.view')
                        ->name('attendance.sessions');
                    Route::middleware('permission:attendance.mark')->group(function () {
                        Route::post('attendance/records', [AdminAttendanceController::class, 'mark'])->name('attendance.records.store');
                        Route::post('attendance/sessions/{schedule}/mark-all-present', [AdminAttendanceController::class, 'markAllPresent'])->name('attendance.sessions.mark-all-present');
                    });

                    Route::middleware('permission:bookings.manage')->group(function () {
                        Route::get('bookings/members', [AdminBookingController::class, 'members'])->name('bookings.members');
                        Route::get('bookings/members/{userPackage}', [AdminBookingController::class, 'member'])->name('bookings.member');
                        Route::get('bookings/therapy-members', [AdminBookingRequestController::class, 'therapyMembers'])->name('bookings.therapy-members');
                        Route::get('private-requests', [AdminBookingRequestController::class, 'privateIndex'])->name('private-requests.index');
                        Route::get('private-requests/{privateRequest}', [AdminBookingRequestController::class, 'privateShow'])->name('private-requests.show');
                        Route::get('therapy-requests', [AdminBookingRequestController::class, 'therapyIndex'])->name('therapy-requests.index');
                        Route::get('therapy-requests/{therapyRequest}', [AdminBookingRequestController::class, 'therapyShow'])->name('therapy-requests.show');
                    });

                    Route::middleware('permission:bookings.private.edit_form')->group(function () {
                        Route::patch('private-requests/{privateRequest}', [AdminBookingRequestController::class, 'privateUpdate'])->name('private-requests.update');
                        Route::patch('therapy-requests/{therapyRequest}', [AdminBookingRequestController::class, 'therapyUpdate'])->name('therapy-requests.update');
                    });

                    Route::middleware('permission:bookings.private.approve')->group(function () {
                        Route::patch('private-requests/{privateRequest}/approve', [AdminBookingRequestController::class, 'privateApprove'])->name('private-requests.approve');
                        Route::patch('private-requests/{privateRequest}/reject', [AdminBookingRequestController::class, 'privateReject'])->name('private-requests.reject');
                        Route::patch('private-requests/{privateRequest}/complete', [AdminBookingRequestController::class, 'privateComplete'])->name('private-requests.complete');
                        Route::patch('therapy-requests/{therapyRequest}/approve', [AdminBookingRequestController::class, 'therapyApprove'])->name('therapy-requests.approve');
                        Route::patch('therapy-requests/{therapyRequest}/reject', [AdminBookingRequestController::class, 'therapyReject'])->name('therapy-requests.reject');
                    });

                    Route::middleware('permission:bookings.cancel_class')->group(function () {
                        Route::patch('private-requests/{privateRequest}/cancel', [AdminBookingRequestController::class, 'privateCancel'])->name('private-requests.cancel');
                        Route::patch('therapy-requests/{therapyRequest}/cancel', [AdminBookingRequestController::class, 'therapyCancel'])->name('therapy-requests.cancel');
                    });
                    Route::patch('bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])
                        ->middleware('permission:bookings.cancel_class')
                        ->name('bookings.cancel');
                });

            Route::post('instructor/schedules/{type}/{id}/cancel-request', [AdminScheduleCancelRequestController::class, 'store'])
                ->middleware(['auth:api', 'permission:schedules.cancel_request.create'])
                ->whereIn('type', ['class', 'private'])
                ->whereNumber('id')
                ->name('instructor.schedules.cancel-request');

            Route::middleware('auth:api')->group(function () {
                Route::get('programs', [ProgramController::class, 'index'])->name('programs.index');
                Route::get('programs/left-off', ProgramLeftOffController::class)->name('programs.left-off');
                Route::get('programs/selection', [ProgramSelectionController::class, 'show'])->name('programs.selection.show');
                Route::post('programs/selection', [ProgramSelectionController::class, 'store'])->name('programs.selection.store');
                Route::post('programs/favorites', [ProgramFavoriteController::class, 'store'])->name('programs.favorites.store');
                Route::get('programs/{program}', [ProgramController::class, 'show'])->name('programs.show');

                Route::get('combos', [ComboController::class, 'index'])->name('combos.index');
                Route::get('combos/{combo}', [ComboController::class, 'show'])->name('combos.show');

                Route::get('lessons/favorites', [LessonFavoriteController::class, 'index'])->name('lessons.favorites.index');
                Route::get('lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
                Route::post('lessons/{lesson}/favorite', [LessonFavoriteController::class, 'store'])->name('lessons.favorite.store');
                Route::delete('lessons/{lesson}/favorite', [LessonFavoriteController::class, 'destroy'])->name('lessons.favorite.destroy');

                Route::post('videos/{video}/play', [VideoPlayController::class, 'play'])->name('videos.play');
                Route::post('videos/{video}/watch-progress', [VideoWatchProgressController::class, 'store'])->name('videos.watch-progress');
            });
        });
    });

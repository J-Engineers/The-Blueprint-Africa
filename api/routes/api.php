<?php

use Illuminate\Http\Request;
use App\Http\Middleware\ActiveUser;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Courses\CourseController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/v1/user/registration', [AuthController::class, 'register'])->name('user.register');
Route::post('/v1/user/registration/verify', [AuthController::class, 'send_registration_verification_email'])->name('user.verify')->middleware(ActiveUser::class);
Route::post('/v1/user/login', [AuthController::class, 'login'])->name('login')->middleware(ActiveUser::class);
Route::post('/v1/user/password/forgot', [AuthController::class, 'forgotPassword'])->name('user.password.forgot')->middleware(ActiveUser::class);
Route::post('/v1/user/password/reset', [AuthController::class, 'resetPassword'])->name('user.password.reset')->middleware(ActiveUser::class);


Route::get('/v1/public/courses', [CourseController::class, 'courses'])->name('public.courses');

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/v1/user/logout', [AuthController::class, 'logout'])->name('user.logout');
    Route::group(['middleware' => ['ActiveUser']], function(){

        // Admin - Users Routes
        Route::get('/v1/admin/users', [AdminController::class, 'getUsers'])->name('users');
        Route::get('/v1/admin/user', [AdminController::class, 'getUser'])->name('user');
        Route::delete('/v1/admin/user/delete', [AdminController::class, 'removeUser'])->name('user.remove');
        Route::get('/v1/admin/user/deactivate', [AdminController::class, 'deactivateUser'])->name('user.deactivate');
        Route::get('/v1/admin/user/activate', [AdminController::class, 'activateUser'])->name('user.activate');
        Route::get('/v1/admin/make/admin', [AdminController::class, 'makeAdmin'])->name('user.makeAdmin');
        Route::get('/v1/admin/cancel/admin', [AdminController::class, 'cancelAdmin'])->name('user.cancelAdmin');


        // Users Routes
        Route::get('/v1/user', [UserController::class, 'details'])->name('user.details');
        Route::put('/v1/user/password', [UserController::class, 'changePassword'])->name('user.password');
        Route::put('/v1/user/update', [UserController::class, 'updateDetails'])->name('user.update');
        Route::post('/v1/user/photo', [UserController::class, 'updatePhoto'])->name('user.photo');
    });
});
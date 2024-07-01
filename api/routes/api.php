<?php

use App\Http\Middleware\ActiveUser;
use App\Http\Middleware\BPMiddleware;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Courses\BPController;
use App\Http\Middleware\DiscountMiddleware;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Courses\DiscountsController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Courses\WithdrawalsController;
use App\Http\Controllers\Courses\AcademyCoursesController;
use App\Http\Controllers\Courses\PaymentDetailsController;
use App\Http\Controllers\Courses\BlueprintCoursesController;
use App\Http\Controllers\Courses\ReferralController;
use App\Http\Controllers\Courses\SubscribeController;
use App\Http\Controllers\Courses\WalletController;


Route::post('/v1/user/registration', [AuthController::class, 'register'])->name('user.register');
Route::post('/v1/user/registration/verify', [AuthController::class, 'send_registration_verification_email'])->name('user.verify')->middleware(ActiveUser::class);
Route::post('/v1/user/login', [AuthController::class, 'login'])->name('login')->middleware(ActiveUser::class);
Route::post('/v1/user/password/forgot', [AuthController::class, 'forgotPassword'])->name('user.password.forgot')->middleware(ActiveUser::class);
Route::post('/v1/user/password/reset', [AuthController::class, 'resetPassword'])->name('user.password.reset')->middleware(ActiveUser::class);


Route::get('/v1/public/academy/courses', [AcademyCoursesController::class, 'index'])->name('public.courses.show')->middleware(DiscountMiddleware::class);
Route::get('/v1/public/academy/course', [AcademyCoursesController::class, 'show'])->name('public.course.show')->middleware(DiscountMiddleware::class);
Route::get('/v1/public/blueprint/courses', [BlueprintCoursesController::class, 'index'])->name('public.courses.blueprint.show')->middleware(DiscountMiddleware::class);
Route::get('/v1/public/blueprint/course', [BlueprintCoursesController::class, 'show'])->name('public.course.blueprint.show')->middleware(DiscountMiddleware::class);


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
        Route::post('/v1/user/wallet', [UserController::class, 'updateWalletToken'])->name('user.updateWalletToken');
        

        // Admin - Academy Courses
        Route::post('/v1/admin/academy/course', [AcademyCoursesController::class, 'store'])->name('admin.courses.add');
        Route::put('/v1/admin/academy/course', [AcademyCoursesController::class, 'update'])->name('admin.courses.update');
        Route::delete('/v1/admin/academy/course', [AcademyCoursesController::class, 'destroy'])->name('admin.courses.remove');

        Route::post('/v1/admin/blueprint/course', [BlueprintCoursesController::class, 'store'])->name('admin.courses.blueprint.add');
        Route::put('/v1/admin/blueprint/course', [BlueprintCoursesController::class, 'update'])->name('admin.courses.blueprint.update');
        Route::delete('/v1/admin/blueprint/course', [BlueprintCoursesController::class, 'destroy'])->name('admin.courses.blueprint.remove');

        // Admin - Discounts
        Route::get('/v1/admin/scholarships', [DiscountsController::class, 'index'])->name('admin.scholarship.show');
        Route::get('/v1/admin/scholarship', [DiscountsController::class, 'show'])->name('admin.scholarship.show');
        Route::post('/v1/admin/scholarship', [DiscountsController::class, 'store'])->name('admin.scholarship.add');
        Route::put('/v1/admin/scholarship', [DiscountsController::class, 'update'])->name('admin.scholarship.update');
        Route::delete('/v1/admin/scholarship', [DiscountsController::class, 'destroy'])->name('admin.scholarship.remove');


        // Admin - BP
        Route::get('/v1/admin/bps', [BPController::class, 'index'])->name('admin.bp.show');
        Route::get('/v1/admin/bp', [BPController::class, 'show'])->name('admin.bp.show');
        Route::post('/v1/admin/bp', [BPController::class, 'store'])->name('admin.bp.add');
        Route::put('/v1/admin/bp', [BPController::class, 'update'])->name('admin.bp.update');
        Route::delete('/v1/admin/bp', [BPController::class, 'destroy'])->name('admin.bp.remove');

        // user - Payment Details
        Route::get('/v1/user/paymentdetails', [PaymentDetailsController::class, 'index'])->name('user.paymentdetails.show');
        Route::get('/v1/user/paymentdetail', [PaymentDetailsController::class, 'show'])->name('user.paymentdetail.show');
        Route::post('/v1/user/paymentdetail', [PaymentDetailsController::class, 'create'])->name('user.paymentdetail.create');
        Route::put('/v1/user/paymentdetail', [PaymentDetailsController::class, 'store'])->name('user.paymentdetail.add');
        Route::delete('/v1/user/paymentdetail', [PaymentDetailsController::class, 'destroy'])->name('user.paymentdetail.remove');

      
        // user - withdrawal
       
        Route::get('/v1/user/withdrawals', [WithdrawalsController::class, 'show_all_user'])->name('user.withdrawal.show_user');
        Route::get('/v1/user/withdrawal', [WithdrawalsController::class, 'show_user'])->name('user.withdrawal.show');
        Route::post('/v1/user/withdrawal', [WithdrawalsController::class, 'create'])->name('user.withdrawal.create');
        Route::put('/v1/user/withdrawal', [WithdrawalsController::class, 'store'])->name('user.withdrawal.add')->middleware(BPMiddleware::class);

        Route::get('/v1/admin/allwithdrawals', [WithdrawalsController::class, 'index'])->name('admin.withdrawals.show');
        Route::get('/v1/admin/withdrawals', [WithdrawalsController::class, 'show'])->name('admin.withdrawals.show');
        Route::get('/v1/admin/withdrawal', [WithdrawalsController::class, 'show_admin'])->name('admin.withdrawals.show');
        Route::put('/v1/admin/withdrawal', [WithdrawalsController::class, 'update'])->name('admin.withdrawal.add');
        Route::delete('/v1/admin/withdrawal', [WithdrawalsController::class, 'destroy'])->name('user.withdrawal.remove');


        // user - subscription

        Route::get('/v1/user/subscriptions', [SubscribeController::class, 'ushows'])->name('user.subscription.ushows');
        Route::get('/v1/user/subscription', [SubscribeController::class, 'ushow'])->name('user.subscription.ushow');
        Route::post('/v1/user/subscription', [SubscribeController::class, 'create'])->name('user.subscription.create')->middleware(BPMiddleware::class)->middleware(DiscountMiddleware::class);
        Route::get('/v1/subscription/payment/callback', [SubscribeController::class, 'store'])->name('user.subscription.store');
        Route::post('/v1/user/subscription/prove', [SubscribeController::class, 'prove_payment'])->name('user.subscription.prove_payment');
        Route::post('/v1/user/subscription/wallet', [SubscribeController::class, 'store_wallet'])->name('user.subscription.wallet.create')->middleware(BPMiddleware::class)->middleware(DiscountMiddleware::class);

        Route::get('/v1/admin/allsubscriptions', [SubscribeController::class, 'index'])->name('admin.allsubscription.index');
        Route::get('/v1/admin/subscriptions', [SubscribeController::class, 'uindexs'])->name('admin.subscription.uindexs');
        Route::get('/v1/admin/subscription', [SubscribeController::class, 'uindex'])->name('admin.subscription.uindex');
        Route::put('/v1/admin/subscription', [SubscribeController::class, 'update'])->name('admin.subscription.update')->middleware(BPMiddleware::class);
        Route::delete('/v1/admin/subscription', [SubscribeController::class, 'destroy'])->name('user.subscription.remove');

        // user - referral
        Route::get('/v1/user/referrals', [ReferralController::class, 'ushows'])->name('user.referrals.show');
        Route::post('/v1/user/referral', [ReferralController::class, 'create'])->name('user.referral.create')->middleware(BPMiddleware::class);
        Route::get('/v1/referral/payment/callback', [ReferralController::class, 'store'])->name('user.referral.store');
        Route::post('/v1/user/referral/prove', [ReferralController::class, 'prove_payment'])->name('user.referral.prove_payment');


        Route::get('/v1/admin/allreferrals', [ReferralController::class, 'index'])->name('admin.referrals.indexs');
        Route::get('/v1/admin/referrals', [ReferralController::class, 'uindexs'])->name('admin.referrals.index');
        Route::put('/v1/admin/referral', [ReferralController::class, 'update'])->name('admin.referral.add')->middleware(BPMiddleware::class);
        

        // user - wallet
        Route::get('/v1/user/wallets', [WalletController::class, 'ushows'])->name('user.wallets.ushows');
        Route::get('/v1/user/wallet', [WalletController::class, 'ushow'])->name('user.wallet.ushow');
        Route::post('/v1/user/wallet', [WalletController::class, 'create'])->name('user.wallet.create')->middleware(BPMiddleware::class)->middleware(DiscountMiddleware::class);
        Route::get('/v1/wallet/payment/callback', [WalletController::class, 'store'])->name('user.wallet.store');
        Route::post('/v1/user/wallet/prove', [WalletController::class, 'prove_payment'])->name('user.wallet.prove_payment');

        Route::get('/v1/admin/allwallets', [WalletController::class, 'index'])->name('admin.allwallets.index');
        Route::get('/v1/admin/wallets', [WalletController::class, 'uindexs'])->name('admin.wallets.uindexs');
        Route::get('/v1/admin/wallet', [WalletController::class, 'uindex'])->name('admin.wallet.uindex');
        Route::put('/v1/admin/wallet', [WalletController::class, 'update'])->name('admin.wallet.update')->middleware(BPMiddleware::class);
        Route::delete('/v1/admin/wallet', [WalletController::class, 'destroy'])->name('user.wallet.remove');
 
    });
});
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
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
Route::prefix('auth')->group(function () {

//    Social Lite Routes
    Route::get('login/{provider}', [AuthController::class, 'redirectToProvider']);
    Route::get('login/{provider}/callback', [AuthController::class, 'handleProviderCallback']);

    //Public Routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('verify-email/{token}/{email}', [AuthController::class, 'verifyEmail']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password/{token}/{email}', [AuthController::class, 'resetPassword']);

    Route::group(['middleware' => ['auth:api', 'role:user']], function () {
        Route::get('logout', [AuthController::class, 'logout']);
    });
});

//Services Routes
Route::group(['middleware' => ['auth:api', 'role:user', 'check-user-status']], function () {

    Route::prefix('service')->group(function () {
        Route::get('all', [ServiceController::class, 'all']);
    });

    Route::prefix('dashboard')->group(function () {
        Route::get('get-all-artists', [DashboardController::class, 'getAllArtists']);
        Route::get('get-suggested-artists', [DashboardController::class, 'getSuggestedArtists']);
        Route::get('get-new-artists', [DashboardController::class, 'getNewArtists']);
        Route::post('get-artist-portfolio', [DashboardController::class, 'getArtistPortfolio']);
        Route::post('get-artist-reviews', [DashboardController::class, 'getArtistReviews']);
        Route::get('get-carousel-images', [DashboardController::class, 'getCarouselImages']);
        Route::post('track-booking', [DashboardController::class, 'trackBooking']);
        Route::get('get-track-booking', [DashboardController::class, 'getTrackBooking']);
    });

    Route::prefix('user')->group(function () {
        Route::get('get-profile-details', [UserController::class, 'getProfileDetails']);
        Route::post('edit-profile', [UserController::class, 'editProfile']);
        Route::post('register-as-artist', [UserController::class, 'registerAsArtist']);
        Route::post('post-your-service', [UserController::class, 'postYourService']);
        Route::post('save-address', [UserController::class, 'saveAddress']);
        Route::get('get-addresses', [UserController::class, 'getAddresses']);
    });

    Route::prefix('payments')->group(function () {
        Route::get('get-details', [PaymentController::class, 'getDetails']);
        Route::post('send', [PaymentController::class, 'sendPayments']);
    });

    Route::prefix('booking')->group(function () {
        Route::post('create', [BookingController::class, 'create']);
        Route::get('get-available-artist-time/{id}', [BookingController::class, 'getAvailableArtistTime']);
        Route::get('all', [BookingController::class, 'all']);
    });

    Route::prefix('contact')->group(function () {
        Route::post('contact-us', [ContactUsController::class, 'contactUs']);
    });

    Route::prefix('settings')->group(function () {
        Route::post('update', [SettingController::class, 'update']);
    });
});

Route::any(
    '{any}',
    function () {
        return response()->json([
            'status_code' => 404,
            'message' => 'Page Not Found. Check method type Post/Get or URL',
        ], 404);
    }
)->where('any', '.*');

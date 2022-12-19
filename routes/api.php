<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
});
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('verify-otp', [AuthController::class, 'varifyOtp']);
Route::post('submit-reset-password', [AuthController::class, 'submitResetPasswordForm']);
Route::post('/profile-update/{id}',[AuthController::class, 'updateProfile']);
Route::get('/my-friend-list',[AuthController::class, 'getMyFriendList']);
Route::post('/add-friend/{id}',[AuthController::class, 'getAddFriend']);
Route::post('/remove-friend/{id}',[AuthController::class, 'removeFriend']);
Route::get('/search-users',[AuthController::class, 'searchUserForFriend']);
Route::post('/accept-request/{id}',[AuthController::class, 'acceptRequest']);
Route::post('/reject-request/{id}',[AuthController::class, 'RejectRequest']);
Route::get('/view-profile/{id}',[AuthController::class, 'viewUserProfile']);


// Route::post('forgot-password','AuthController@forgotPassowrd');getMyFriendList

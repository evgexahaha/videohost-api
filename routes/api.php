<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SubscriberController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\VideoLikerController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Videos
    Route::apiResource('videos', VideoController::class);
    Route::get('/videos/trending', [VideoController::class, 'trending']);
    Route::get('/videos/{videoId}/likers', [VideoLikerController::class, 'index']);

    // Likes
    Route::post('/videos/{videoId}/like', [LikeController::class, 'toggle']);
    Route::get('/likes', [LikeController::class, 'index']);

    // Comments
    Route::get('/videos/{videoId}/comments', [CommentController::class, 'index']);
    Route::post('/videos/{videoId}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{id}', [CommentController::class, 'update']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

    // Subscriptions
    Route::post('/channels/{channelId}/subscribe', [SubscriptionController::class, 'toggle']);
    Route::get('/channels/{channelId}/subscription/status', [SubscriptionController::class, 'status']);
    Route::get('/channels/{channelId}/subscribers', [SubscriberController::class, 'index']);
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);

    // Profile
    Route::get('/profile/{id?}', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::get('/profile/videos/my', [ProfileController::class, 'myVideos']);
    Route::delete('/profile/delete-account', [ProfileController::class, 'destroyAccount']);
});

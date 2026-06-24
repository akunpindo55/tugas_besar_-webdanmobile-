<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\ForumController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Campus Connect (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Auth Routes (Guest)
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Authenticated Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth Actions
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/change-password', [AuthController::class, 'changePassword']);

        // User Module
        Route::get('/users/search', [UserController::class, 'search']);
        Route::get('/users/{username}', [UserController::class, 'show']);
        Route::put('/users/profile', [UserController::class, 'updateProfile']);
        Route::post('/users/avatar', [UserController::class, 'updateAvatar']);
        Route::post('/users/{id}/block', [UserController::class, 'block']);
        Route::post('/users/{id}/unblock', [UserController::class, 'unblock']);

        // Chat Module (Conversations)
        Route::get('/conversations', [ConversationController::class, 'index']);
        Route::post('/conversations', [ConversationController::class, 'store']);
        Route::post('/conversations/{id}/invite', [ConversationController::class, 'invite']);
        Route::post('/invitations/{id}/respond', [ConversationController::class, 'respond']);
        Route::post('/conversations/{id}/leave', [ConversationController::class, 'leave']);

        // Messages Module
        Route::get('/conversations/{id}/messages', [MessageController::class, 'index']);
        Route::post('/conversations/{id}/messages', [MessageController::class, 'store']);
        Route::post('/messages/{id}/read', [MessageController::class, 'read']);
        Route::post('/conversations/{id}/read-all', [MessageController::class, 'readAll']);

        // Feed / Posts Module
        Route::get('/posts', [PostController::class, 'index']);
        Route::post('/posts', [PostController::class, 'store']);
        Route::put('/posts/{id}', [PostController::class, 'update']);
        Route::delete('/posts/{id}', [PostController::class, 'destroy']);
        Route::post('/posts/{id}/comments', [PostController::class, 'comment']);
        Route::post('/posts/{id}/reactions', [PostController::class, 'react']);

        // Forums Module
        Route::get('/forums', [ForumController::class, 'index']);
        Route::get('/forums/my', [ForumController::class, 'indexUser']);
        Route::post('/forums', [ForumController::class, 'store']);
        Route::post('/forums/{id}/join', [ForumController::class, 'join']);
        Route::post('/forums/{id}/invite', [ForumController::class, 'invite']);
        Route::post('/forum-invitations/{id}/respond', [ForumController::class, 'respond']);
        Route::post('/forums/{id}/leave', [ForumController::class, 'leave']);
        Route::post('/forums/{id}/kick/{memberId}', [ForumController::class, 'kick']);

        // Forum Topics & Comments
        Route::get('/forums/{id}/topics', [ForumController::class, 'listTopics']);
        Route::post('/forums/{id}/topics', [ForumController::class, 'createTopic']);
        Route::get('/topics/{id}', [ForumController::class, 'showTopic']);
        Route::post('/topics/{id}/comments', [ForumController::class, 'replyTopic']);

        // Moderation / Reports Module
        Route::post('/reports', [ReportController::class, 'store']);
        Route::get('/admin/reports', [ReportController::class, 'indexAdmin']);
        Route::put('/admin/reports/{id}/resolve', [ReportController::class, 'resolveAdmin']);

        // Notifications Module
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::put('/notifications/{id}/read', [NotificationController::class, 'read']);
        Route::put('/notifications/read-all', [NotificationController::class, 'readAll']);

        // Device Tokens (Push Notifications)
        Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    });
});

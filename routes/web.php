<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ChatController;
use App\Http\Controllers\Web\ForumController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\PostController;
use App\Http\Controllers\Web\ProfileController;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Public Routes (no auth)
Route::get('/', function () {
    return view('welcome');
})->name('landing');

// Authenticated Web Routes
Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Chat JSON Endpoints (must be before /chat/{id})
    Route::get('/chat/users/search', [ChatController::class, 'searchUsers']);
    Route::post('/chat/create', [ChatController::class, 'createConversation']);
    Route::post('/chat/messages/{id}/read', [ChatController::class, 'readMessage']);
    Route::delete('/chat/messages/{id}', [ChatController::class, 'destroy']);
    Route::post('/invitations/{id}/respond', [ChatController::class, 'respondInvitation']);

    // Chat Pages (with {id} parameter)
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{id}', [ChatController::class, 'show'])->name('chat.show');

    // Chat JSON Endpoints (with {id} parameter)
    Route::get('/chat/{id}/messages', [ChatController::class, 'getMessages']);
    Route::post('/chat/{id}/messages', [ChatController::class, 'sendMessage']);
    Route::post('/chat/{id}/read-all', [ChatController::class, 'readAll']);
    Route::post('/chat/{id}/invite', [ChatController::class, 'invite']);
    Route::post('/chat/{id}/leave', [ChatController::class, 'leave']);

    // Post JSON Endpoints
    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    Route::post('/posts/{post}/comments', [PostController::class, 'comment']);
    Route::post('/posts/{post}/reactions', [PostController::class, 'react']);

    // Profile Pages
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'avatar'])->name('profile.avatar');
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');
    Route::get('/profile/{username}', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile/{user}/block', [ProfileController::class, 'block'])->name('profile.block');
    Route::post('/profile/{user}/unblock', [ProfileController::class, 'unblock'])->name('profile.unblock');

    // Forum Pages
    Route::get('/forums', [ForumController::class, 'index'])->name('forums.index');
    Route::get('/forums/my', [ForumController::class, 'myForums'])->name('forums.my');
    Route::get('/forums/create', [ForumController::class, 'create'])->name('forums.create');
    Route::post('/forums', [ForumController::class, 'store']);
    Route::get('/forums/{id}', [ForumController::class, 'show'])->name('forums.show');
    Route::post('/forums/{id}/join', [ForumController::class, 'join'])->name('forums.join');
    Route::post('/forums/{id}/leave', [ForumController::class, 'leave'])->name('forums.leave');
    Route::post('/forums/{id}/invite', [ForumController::class, 'invite']);
    Route::post('/forums/{id}/kick/{memberId}', [ForumController::class, 'kick']);
    Route::post('/forums/{id}/topics', [ForumController::class, 'createTopic'])->name('forums.topics');
    Route::get('/topics/{id}', [ForumController::class, 'showTopic'])->name('topics.show');
    Route::post('/topics/{id}/comments', [ForumController::class, 'replyTopic'])->name('topics.comments');
    Route::post('/forum-invitations/{id}/respond', [ForumController::class, 'respondInvitation']);
});

<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels - Campus Connect
|--------------------------------------------------------------------------
*/

// Private channel for personal notifications
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for conversation messages and read receipts
Broadcast::channel('conversation.{id}', function ($user, $id) {
    $conversation = Conversation::find($id);
    if (!$conversation) {
        return false;
    }
    return $conversation->members()->where('users.id', $user->id)->exists();
});

// Presence channel for online indicators and typing states
Broadcast::channel('presence-conversation.{id}', function ($user, $id) {
    $conversation = Conversation::find($id);
    if (!$conversation) {
        return false;
    }
    
    $isMember = $conversation->members()->where('users.id', $user->id)->exists();
    if ($isMember) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
        ];
    }
    
    return false;
});

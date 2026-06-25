<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function getNotifications(User $user, int $limit = 20): LengthAwarePaginator
    {
        return $user->notifications()->latest()->paginate($limit);
    }

    public function markRead(User $user, string $notificationId): void
    {
        $notification = $user->notifications()->findOrFail($notificationId);
        $notification->markAsRead();
    }

    public function markAllRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function delete(User $user, string $notificationId): void
    {
        $notification = $user->notifications()->findOrFail($notificationId);
        $notification->delete();
    }

    public function deleteAll(User $user): void
    {
        $user->notifications()->delete();
    }
}

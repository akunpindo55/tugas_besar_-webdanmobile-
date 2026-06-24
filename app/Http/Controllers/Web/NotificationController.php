<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Contracts\View\View;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function index(): View
    {
        $notifications = $this->notificationService->getNotifications(auth()->user(), 50);

        return view('notifications.index', compact('notifications'));
    }
}

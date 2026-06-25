<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        $paginator = $this->notificationService->getNotifications($request->user(), $limit);

        return $this->paginatedResponse(
            NotificationResource::collection($paginator),
            'Daftar notifikasi berhasil dimuat.'
        );
    }

    public function read(Request $request, string $id): JsonResponse
    {
        try {
            $this->notificationService->markRead($request->user(), $id);
            return $this->successResponse(null, 'Notifikasi berhasil ditandai dibaca.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function readAll(Request $request): JsonResponse
    {
        $this->notificationService->markAllRead($request->user());
        return $this->successResponse(null, 'Semua notifikasi berhasil ditandai dibaca.');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user());
        return $this->successResponse(['count' => $count]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->notificationService->delete($request->user(), $id);
            return $this->successResponse(null, 'Notifikasi berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $this->notificationService->deleteAll($request->user());
        return $this->successResponse(null, 'Semua notifikasi berhasil dihapus.');
    }
}

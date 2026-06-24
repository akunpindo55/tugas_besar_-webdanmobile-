<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends ApiController
{
    public function __construct(
        protected MessageService $messageService
    ) {}

    public function index(Request $request, int $conversationId): JsonResponse
    {
        try {
            $limit = (int) $request->query('limit', 20);
            $paginator = $this->messageService->getHistory($request->user(), $conversationId, $limit);

            $messages = MessageResource::collection($paginator);

            return response()->json([
                'success' => true,
                'message' => 'Riwayat pesan berhasil dimuat.',
                'data' => $messages,
                'meta' => [
                    'next_cursor' => $paginator->nextCursor()?->encode(),
                    'prev_cursor' => $paginator->previousCursor()?->encode(),
                    'per_page' => $paginator->perPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(StoreMessageRequest $request, int $conversationId): JsonResponse
    {
        try {
            $message = $this->messageService->sendMessage(
                $request->user(),
                $conversationId,
                $request->validated(),
                $request->file('file')
            );

            return $this->successResponse(
                new MessageResource($message),
                'Pesan berhasil dikirim.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function read(Request $request, int $id): JsonResponse
    {
        try {
            $this->messageService->markAsRead($request->user(), $id);
            return $this->successResponse(null, 'Pesan berhasil ditandai dibaca.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function readAll(Request $request, int $conversationId): JsonResponse
    {
        try {
            $this->messageService->markAllAsRead($request->user(), $conversationId);
            return $this->successResponse(null, 'Semua pesan berhasil ditandai dibaca.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}

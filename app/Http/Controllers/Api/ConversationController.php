<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\InviteMemberRequest;
use App\Http\Requests\RespondInvitationRequest;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\GroupInvitationResource;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends ApiController
{
    public function __construct(
        protected ConversationService $conversationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $conversations = $this->conversationService->listConversations($request->user());
        return $this->successResponse(
            ConversationResource::collection($conversations),
            'Daftar percakapan berhasil dimuat.'
        );
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        if ($request->input('type') === 'direct') {
            $conversation = $this->conversationService->startDirect(
                $request->user(),
                $request->input('target_user_id')
            );
        } else {
            $conversation = $this->conversationService->createGroup(
                $request->user(),
                $request->only(['name', 'description', 'avatar'])
            );
        }

        $conversation->load(['members']);

        return $this->successResponse(
            new ConversationResource($conversation),
            'Percakapan berhasil dibuat.',
            201
        );
    }

    public function invite(InviteMemberRequest $request, int $id): JsonResponse
    {
        try {
            $invitation = $this->conversationService->inviteToGroup(
                $request->user(),
                $id,
                $request->input('invited_user_id')
            );

            return $this->successResponse(
                new GroupInvitationResource($invitation),
                'Undangan grup berhasil dikirim.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function respond(RespondInvitationRequest $request, int $id): JsonResponse
    {
        try {
            $invitation = $this->conversationService->respondToInvitation(
                $request->user(),
                $id,
                $request->input('status')
            );

            return $this->successResponse(
                new GroupInvitationResource($invitation),
                'Undangan berhasil direspon.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function leave(Request $request, int $id): JsonResponse
    {
        try {
            $this->conversationService->leaveGroup($request->user(), $id);
            return $this->successResponse(null, 'Berhasil meninggalkan grup.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $this->conversationService->deleteForMe($request->user(), $id);
            return $this->successResponse(null, 'Percakapan berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}

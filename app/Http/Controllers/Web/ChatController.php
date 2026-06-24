<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ConversationService;
use App\Services\MessageService;
use App\Services\UserService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        protected ConversationService $conversationService,
        protected MessageService $messageService,
        protected UserService $userService
    ) {}

    public function index(Request $request)
    {
        $conversations = $this->conversationService->listConversations($request->user());

        // Transform for JavaScript consumption
        $conversations = $conversations->map(function ($conv) use ($request) {
            return $this->serializeConversation($conv, $request->user());
        });

        $invitations = $request->user()->groupInvitations()
            ->with(['conversation', 'inviter'])
            ->where('status', 'pending')
            ->get();

        return view('web.chat.index', compact('conversations', 'invitations'));
    }

    public function show(Request $request, $id)
    {
        $conversations = $this->conversationService->listConversations($request->user());

        // Resolve display names for direct conversations in sidebar
        $conversations->each(function ($conv) use ($request) {
            if ($conv->type === 'direct') {
                $otherUser = $conv->members->first(function ($m) use ($request) {
                    return $m->id !== $request->user()->id;
                });
                if ($otherUser) {
                    $conv->name = $otherUser->name;
                    $conv->avatar = $otherUser->avatar;
                }
            }
        });

        $currentConversation = $conversations->firstWhere('id', (int) $id);

        if (!$currentConversation) {
            abort(404, 'Percakapan tidak ditemukan.');
        }

        return view('web.chat.show', compact('conversations', 'currentConversation'));
    }

    protected function serializeConversation($conv, $user)
    {
        $data = $conv->toArray();

        // Resolve display name for direct chats
        if ($conv->type === 'direct') {
            $otherUser = $conv->members->first(function ($m) use ($user) {
                return $m->id !== $user->id;
            });
            if ($otherUser) {
                $data['name'] = $otherUser->name;
                $data['avatar'] = $otherUser->avatar;
            }
        }

        // Normalize last_message key
        $messages = $conv->relationsToArray()['messages'] ?? [];
        $data['last_message'] = !empty($messages) ? $messages[0] : null;

        return $data;
    }

    public function getMessages(Request $request, $id)
    {
        try {
            $cursor = $request->query('cursor');
            $limit = (int) $request->query('limit', 30);

            $paginator = $this->messageService->getHistory($request->user(), $id, $limit);

            $messages = $paginator->items();

            return response()->json([
                'data' => array_reverse($messages),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
                'has_more' => $paginator->hasMorePages(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function sendMessage(Request $request, $id)
    {
        $validated = $request->validate([
            'content' => 'required_without:file|string|nullable',
            'message_type' => 'sometimes|in:text,image,file,voice',
            'reply_to' => 'sometimes|integer|exists:messages,id',
        ]);

        try {
            $message = $this->messageService->sendMessage(
                $request->user(),
                $id,
                [
                    'message_type' => $validated['message_type'] ?? 'text',
                    'body' => $validated['content'] ?? null,
                    'reply_to' => $validated['reply_to'] ?? null,
                ],
                $request->file('file')
            );

            $message->load('sender');

            return response()->json([
                'data' => $message
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function readAll(Request $request, $id)
    {
        try {
            $this->messageService->markAllAsRead($request->user(), $id);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function readMessage(Request $request, $id)
    {
        try {
            $this->messageService->markAsRead($request->user(), $id);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $this->messageService->deleteMessage($request->user(), $id);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function createConversation(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:direct,group',
            'target_user_id' => 'required_if:type,direct|integer|exists:users,id',
            'name' => 'required_if:type,group|string|max:100',
            'description' => 'sometimes|string|max:255',
        ]);

        try {
            if ($validated['type'] === 'direct') {
                $conversation = $this->conversationService->startDirect(
                    $request->user(),
                    $validated['target_user_id']
                );
            } else {
                $conversation = $this->conversationService->createGroup(
                    $request->user(),
                    $validated
                );
            }

            $conversation->load('members');

            return response()->json([
                'data' => $conversation
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function invite(Request $request, $id)
    {
        $validated = $request->validate([
            'invited_user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $invitation = $this->conversationService->inviteToGroup(
                $request->user(),
                $id,
                $validated['invited_user_id']
            );

            return response()->json([
                'data' => $invitation
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function respondInvitation(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:accepted,declined',
        ]);

        try {
            $invitation = $this->conversationService->respondToInvitation(
                $request->user(),
                $id,
                $validated['status']
            );

            return response()->json([
                'data' => $invitation
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function leave(Request $request, $id)
    {
        try {
            $this->conversationService->leaveGroup($request->user(), $id);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function searchUsers(Request $request)
    {
        $query = $request->query('q');

        if (!$query || strlen($query) < 1) {
            return response()->json(['data' => []]);
        }

        try {
            $users = $this->userService->search($query, $request->user());
            return response()->json([
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}

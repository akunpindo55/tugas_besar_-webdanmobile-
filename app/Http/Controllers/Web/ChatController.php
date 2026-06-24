<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ConversationService;
use App\Services\MessageService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        protected ConversationService $conversationService,
        protected MessageService $messageService
    ) {}

    public function index(Request $request)
    {
        $conversations = $this->conversationService->listConversations($request->user());
        
        return view('web.chat.index', compact('conversations'));
    }

    public function getMessages(Request $request, $id)
    {
        $paginator = $this->messageService->getHistory($request->user(), $id, 50);
        return response()->json([
            'data' => array_reverse($paginator->items()) // balik agar yang terbaru di bawah
        ]);
    }

    public function sendMessage(Request $request, $id)
    {
        $validated = $request->validate(['content' => 'required|string']);
        
        $message = $this->messageService->sendMessage(
            $request->user(),
            $id,
            $validated,
            null
        );

        // Load relasi sender biar alpine bisa render nama
        $message->load('sender');

        return response()->json([
            'data' => $message
        ]);
    }
}

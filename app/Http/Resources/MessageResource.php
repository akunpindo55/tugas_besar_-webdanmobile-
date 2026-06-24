<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'message_type' => $this->message_type,
            'body' => $this->body,
            'file_url' => $this->file_url,
            'reply_to' => $this->reply_to,
            'reply_to_message' => $this->reply_to ? [
                'id' => $this->replyTo->id,
                'body' => $this->replyTo->body,
                'message_type' => $this->replyTo->message_type,
            ] : null,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

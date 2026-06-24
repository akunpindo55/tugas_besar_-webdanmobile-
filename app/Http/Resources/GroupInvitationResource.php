<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'conversation_name' => $this->conversation?->name,
            'invited_by' => $this->invited_by,
            'inviter' => new UserResource($this->inviter),
            'invited_user_id' => $this->invited_user_id,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'responded_at' => $this->responded_at?->toIso8601String(),
        ];
    }
}

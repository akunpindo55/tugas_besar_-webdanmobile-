<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForumInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'forum_id' => $this->forum_id,
            'forum_name' => $this->forum?->name,
            'invited_by' => $this->invited_by,
            'inviter' => new UserResource($this->inviter),
            'invited_user_id' => $this->invited_user_id,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'responded_at' => $this->responded_at?->toIso8601String(),
        ];
    }
}

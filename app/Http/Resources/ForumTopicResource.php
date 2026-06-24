<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForumTopicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'forum_id' => $this->forum_id,
            'user' => new UserResource($this->user),
            'title' => $this->title,
            'content' => $this->content,
            'comments_count' => $this->comments()->count(),
            'comments' => ForumCommentResource::collection($this->whenLoaded('comments')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

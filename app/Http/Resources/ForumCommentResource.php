<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForumCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'topic_id' => $this->topic_id,
            'user' => new UserResource($this->user),
            'parent_comment_id' => $this->parent_comment_id,
            'content' => $this->content,
            'file_url' => $this->file_url,
            'media_type' => $this->media_type,
            'replies' => ForumCommentResource::collection($this->whenLoaded('replies')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reactionsCount = $this->reactions->groupBy('reaction_type')->map->count();
        $userReaction = $request->user()
            ? $this->reactions->firstWhere('user_id', $request->user()->id)?->reaction_type
            : null;

        return [
            'id' => $this->id,
            'user' => new UserResource($this->user),
            'content' => $this->content,
            'visibility' => $this->visibility,
            'media' => $this->media->map(function ($m) {
                return [
                    'id' => $m->id,
                    'media_url' => $m->media_url,
                    'media_type' => $m->media_type,
                ];
            }),
            'comments_count' => $this->comments()->count(),
            'latest_comments' => PostCommentResource::collection($this->whenLoaded('comments')),
            'reactions_summary' => $reactionsCount,
            'reactions_total' => $this->reactions->count(),
            'user_reaction' => $userReaction,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

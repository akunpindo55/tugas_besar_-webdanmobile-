<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = $this->name;
        $avatar = $this->avatar;

        if ($this->type === 'direct' && $request->user()) {
            $otherUser = $this->members->first(function ($m) use ($request) {
                return $m->id !== $request->user()->id;
            });
            if ($otherUser) {
                $name = $otherUser->name;
                $avatar = $otherUser->avatar;
            }
        }

        return [
            'id' => $this->id,
            'name' => $name,
            'description' => $this->description,
            'type' => $this->type,
            'created_by' => $this->created_by,
            'avatar' => $avatar,
            'members' => UserResource::collection($this->whenLoaded('members')),
            'last_message' => new MessageResource($this->messages->first()),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'created_by', 'is_private'])]
class Forum extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'forum_members')
            ->withPivot('role', 'joined_at');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ForumInvitation::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(ForumTopic::class)->orderBy('created_at', 'desc');
    }
}

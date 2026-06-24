<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\ForumInvitation;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_forum(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/forums', [
                'name' => 'IT Connect',
                'description' => 'A forum for IT students.',
                'is_private' => false,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'IT Connect');

        $this->assertDatabaseHas('forums', [
            'name' => 'IT Connect',
            'is_private' => 0,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('forum_members', [
            'forum_id' => $response->json('data.id'),
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_user_can_join_public_forum(): void
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_private' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/forums/{$forum->id}/join");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('forum_members', [
            'forum_id' => $forum->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);
    }

    public function test_user_cannot_join_private_forum_directly(): void
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_private' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/forums/{$forum->id}/join");

        $response->assertStatus(400); // throws exception 'Forum privat'
    }

    public function test_owner_can_invite_to_private_forum(): void
    {
        $owner = User::factory()->create();
        $invited = User::factory()->create();
        $forum = Forum::factory()->create(['is_private' => true, 'created_by' => $owner->id]);
        $forum->members()->attach($owner->id, ['role' => 'owner']);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/forums/{$forum->id}/invite", [
                'invited_user_id' => $invited->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('forum_invitations', [
            'forum_id' => $forum->id,
            'invited_by' => $owner->id,
            'invited_user_id' => $invited->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_respond_to_forum_invitation(): void
    {
        $owner = User::factory()->create();
        $invited = User::factory()->create();
        $forum = Forum::factory()->create(['is_private' => true, 'created_by' => $owner->id]);
        $forum->members()->attach($owner->id, ['role' => 'owner']);

        $invitation = ForumInvitation::create([
            'forum_id' => $forum->id,
            'invited_by' => $owner->id,
            'invited_user_id' => $invited->id,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($invited, 'sanctum')
            ->postJson("/api/v1/forum-invitations/{$invitation->id}/respond", [
                'status' => 'accepted',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('forum_members', [
            'forum_id' => $forum->id,
            'user_id' => $invited->id,
            'role' => 'member',
        ]);
    }

    public function test_user_can_leave_forum(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $forum = Forum::factory()->create(['created_by' => $owner->id]);
        $forum->members()->attach($owner->id, ['role' => 'owner']);
        $forum->members()->attach($member->id, ['role' => 'member']);

        $response = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/forums/{$forum->id}/leave");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('forum_members', [
            'forum_id' => $forum->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_owner_can_kick_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $forum = Forum::factory()->create(['created_by' => $owner->id]);
        $forum->members()->attach($owner->id, ['role' => 'owner']);
        $forum->members()->attach($member->id, ['role' => 'member']);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/forums/{$forum->id}/kick/{$member->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('forum_members', [
            'forum_id' => $forum->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_member_can_create_discussion_topic(): void
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create();
        $forum->members()->attach($user->id, ['role' => 'member']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/forums/{$forum->id}/topics", [
                'title' => 'My discussion title',
                'content' => 'This is the discussion content.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'My discussion title');

        $this->assertDatabaseHas('forum_topics', [
            'forum_id' => $forum->id,
            'user_id' => $user->id,
            'title' => 'My discussion title',
        ]);
    }

    public function test_member_can_reply_to_topic(): void
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create();
        $forum->members()->attach($user->id, ['role' => 'member']);
        $topic = ForumTopic::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/topics/{$topic->id}/comments", [
                'content' => 'This is a discussion reply.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'This is a discussion reply.');

        $this->assertDatabaseHas('forum_comments', [
            'topic_id' => $topic->id,
            'user_id' => $user->id,
            'content' => 'This is a discussion reply.',
        ]);
    }
}

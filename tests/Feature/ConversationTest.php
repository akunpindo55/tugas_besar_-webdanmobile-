<?php

namespace Tests\Feature;

use App\Models\BlockedUser;
use App\Models\Conversation;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_direct_conversation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $response = $this->actingAs($userA, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'target_user_id' => $userB->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'direct');

        $this->assertDatabaseHas('conversation_members', [
            'user_id' => $userA->id,
        ]);
        $this->assertDatabaseHas('conversation_members', [
            'user_id' => $userB->id,
        ]);
    }

    public function test_user_cannot_start_direct_conversation_if_blocked(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // userB blocks userA
        BlockedUser::create([
            'user_id' => $userB->id,
            'blocked_user_id' => $userA->id,
        ]);

        $response = $this->actingAs($userA, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'target_user_id' => $userB->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_user_can_create_group_conversation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'group',
                'name' => 'Study Group',
                'description' => 'A group for studying.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Study Group');

        $this->assertDatabaseHas('conversations', [
            'name' => 'Study Group',
            'type' => 'group',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('conversation_members', [
            'conversation_id' => $response->json('data.id'),
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_user_can_invite_others_to_group(): void
    {
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $group = Conversation::factory()->create([
            'type' => 'group',
            'name' => 'My Group',
            'created_by' => $owner->id,
        ]);
        $group->members()->attach($owner->id, ['role' => 'owner']);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/conversations/{$group->id}/invite", [
                'invited_user_id' => $targetUser->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('group_invitations', [
            'conversation_id' => $group->id,
            'invited_by' => $owner->id,
            'invited_user_id' => $targetUser->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_respond_to_group_invitation(): void
    {
        $owner = User::factory()->create();
        $invited = User::factory()->create();
        $group = Conversation::factory()->create(['type' => 'group', 'created_by' => $owner->id]);
        $group->members()->attach($owner->id, ['role' => 'owner']);

        $invitation = GroupInvitation::create([
            'conversation_id' => $group->id,
            'invited_by' => $owner->id,
            'invited_user_id' => $invited->id,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        // Accept invitation
        $response = $this->actingAs($invited, 'sanctum')
            ->postJson("/api/v1/invitations/{$invitation->id}/respond", [
                'status' => 'accepted',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('conversation_members', [
            'conversation_id' => $group->id,
            'user_id' => $invited->id,
            'role' => 'member',
        ]);
    }

    public function test_user_can_leave_group(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $group = Conversation::factory()->create(['type' => 'group', 'created_by' => $owner->id]);
        $group->members()->attach($owner->id, ['role' => 'owner']);
        $group->members()->attach($member->id, ['role' => 'member']);

        $response = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/conversations/{$group->id}/leave");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('conversation_members', [
            'conversation_id' => $group->id,
            'user_id' => $member->id,
        ]);
    }
}

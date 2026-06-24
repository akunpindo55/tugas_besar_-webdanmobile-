<?php

namespace Tests\Feature;

use App\Models\BlockedUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_search_others_by_username(): void
    {
        $currentUser = User::factory()->create();
        $targetUser1 = User::factory()->create(['username' => 'alice_smith']);
        $targetUser2 = User::factory()->create(['username' => 'bob_jones']);

        $response = $this->actingAs($currentUser, 'sanctum')
            ->getJson('/api/v1/users/search?username=alice');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.username', 'alice_smith');
    }

    public function test_search_filters_out_blocked_users(): void
    {
        $currentUser = User::factory()->create();
        $blockedUser = User::factory()->create(['username' => 'alice_smith']);
        $blockingUser = User::factory()->create(['username' => 'alice_jones']);

        // Current user blocks alice_smith
        BlockedUser::create([
            'user_id' => $currentUser->id,
            'blocked_user_id' => $blockedUser->id,
        ]);

        // alice_jones blocks current user
        BlockedUser::create([
            'user_id' => $blockingUser->id,
            'blocked_user_id' => $currentUser->id,
        ]);

        $response = $this->actingAs($currentUser, 'sanctum')
            ->getJson('/api/v1/users/search?username=alice');

        // Neither blockedUser nor blockingUser should be returned
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_user_can_view_profile_by_username(): void
    {
        $currentUser = User::factory()->create();
        $targetUser = User::factory()->create(['username' => 'john_doe']);

        $response = $this->actingAs($currentUser, 'sanctum')
            ->getJson('/api/v1/users/john_doe');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.username', 'john_doe');
    }

    public function test_user_can_update_profile(): void
    {
        $currentUser = User::factory()->create([
            'name' => 'Original Name',
            'bio' => 'Original Bio',
        ]);

        $response = $this->actingAs($currentUser, 'sanctum')
            ->putJson('/api/v1/users/profile', [
                'name' => 'New Name',
                'bio' => 'New Bio',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.bio', 'New Bio');

        $this->assertDatabaseHas('users', [
            'id' => $currentUser->id,
            'name' => 'New Name',
            'bio' => 'New Bio',
        ]);
    }

    public function test_user_can_block_and_unblock_another_user(): void
    {
        $currentUser = User::factory()->create();
        $targetUser = User::factory()->create();

        // Block
        $responseBlock = $this->actingAs($currentUser, 'sanctum')
            ->postJson("/api/v1/users/{$targetUser->id}/block");

        $responseBlock->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('blocked_users', [
            'user_id' => $currentUser->id,
            'blocked_user_id' => $targetUser->id,
        ]);

        // Unblock
        $responseUnblock = $this->actingAs($currentUser, 'sanctum')
            ->postJson("/api/v1/users/{$targetUser->id}/unblock");

        $responseUnblock->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('blocked_users', [
            'user_id' => $currentUser->id,
            'blocked_user_id' => $targetUser->id,
        ]);
    }
}

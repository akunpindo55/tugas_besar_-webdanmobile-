<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'This is my first post!',
                'visibility' => 'public',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'This is my first post!');

        $this->assertDatabaseHas('posts', [
            'content' => 'This is my first post!',
            'visibility' => 'public',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_update_own_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'content' => 'Old content']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/posts/{$post->id}", [
                'content' => 'Updated content',
                'visibility' => 'public',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Updated content');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_user_cannot_update_other_user_post(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $userB->id, 'content' => 'Old content']);

        $response = $this->actingAs($userA, 'sanctum')
            ->putJson("/api/v1/posts/{$post->id}", [
                'content' => 'Updated content',
                'visibility' => 'public',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_user_cannot_delete_other_user_post_unless_admin(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $postOwner = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $postOwner->id]);

        // Non-admin attempt
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/posts/{$post->id}");
        $response->assertStatus(403);

        // Admin attempt
        $responseAdmin = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/posts/{$post->id}");
        $responseAdmin->assertStatus(200);

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_feed_displays_correct_posts_based_on_visibility(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();

        // Public post from other user
        Post::factory()->create([
            'user_id' => $otherUser->id,
            'content' => 'Public post',
            'visibility' => 'public',
        ]);

        // Private post from other user (should be hidden)
        Post::factory()->create([
            'user_id' => $otherUser->id,
            'content' => 'Private post from other',
            'visibility' => 'private',
        ]);

        // Private post from current user (should be visible)
        Post::factory()->create([
            'user_id' => $currentUser->id,
            'content' => 'My private post',
            'visibility' => 'private',
        ]);

        $response = $this->actingAs($currentUser, 'sanctum')
            ->getJson('/api/v1/posts');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // only public other post + private my post
    }

    public function test_user_can_comment_on_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'comment' => 'This is a comment.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.comment', 'This is a comment.');

        $this->assertDatabaseHas('post_comments', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'comment' => 'This is a comment.',
        ]);
    }

    public function test_user_can_toggle_reaction_on_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // 1. Add reaction
        $responseAdd = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/reactions", [
                'reaction_type' => 'love',
            ]);

        $responseAdd->assertStatus(200)
            ->assertJsonPath('data.status', 'added');

        $this->assertDatabaseHas('post_reactions', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'reaction_type' => 'love',
        ]);

        // 2. Change reaction type
        $responseChange = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/reactions", [
                'reaction_type' => 'laugh',
            ]);

        $responseChange->assertStatus(200)
            ->assertJsonPath('data.status', 'added')
            ->assertJsonPath('data.reaction_type', 'laugh');

        $this->assertDatabaseHas('post_reactions', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'reaction_type' => 'laugh',
        ]);

        // 3. Toggle off (remove) reaction
        $responseRemove = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/reactions", [
                'reaction_type' => 'laugh',
            ]);

        $responseRemove->assertStatus(200)
            ->assertJsonPath('data.status', 'removed');

        $this->assertDatabaseMissing('post_reactions', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }
}

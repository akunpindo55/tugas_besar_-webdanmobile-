<?php

namespace Tests\Feature;

use App\Models\BlockedUser;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_message(): void
    {
        $user = User::factory()->create();
        $group = Conversation::factory()->create(['type' => 'group']);
        $group->members()->attach($user->id, ['role' => 'member']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/conversations/{$group->id}/messages", [
                'message_type' => 'text',
                'body' => 'Hello World!',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'Hello World!');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $group->id,
            'sender_id' => $user->id,
            'body' => 'Hello World!',
        ]);
    }

    public function test_user_cannot_send_message_in_direct_chat_if_blocked(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // userB blocks userA
        BlockedUser::create([
            'user_id' => $userB->id,
            'blocked_user_id' => $userA->id,
        ]);

        // Direct conversation
        $direct = Conversation::factory()->create(['type' => 'direct']);
        $direct->members()->attach([
            $userA->id => ['role' => 'member'],
            $userB->id => ['role' => 'member'],
        ]);

        $response = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/conversations/{$direct->id}/messages", [
                'message_type' => 'text',
                'body' => 'Blocked hello',
            ]);

        $response->assertStatus(400); // Exception message thrown is converted to error response
    }

    public function test_user_can_retrieve_message_history(): void
    {
        $user = User::factory()->create();
        $group = Conversation::factory()->create(['type' => 'group']);
        $group->members()->attach($user->id, ['role' => 'member']);

        // Create messages
        Message::factory()->count(5)->create([
            'conversation_id' => $group->id,
            'sender_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/conversations/{$group->id}/messages");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure(['success', 'message', 'data', 'meta' => ['next_cursor', 'prev_cursor', 'per_page']]);
    }

    public function test_user_can_mark_message_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $group = Conversation::factory()->create(['type' => 'group']);
        $group->members()->attach($user->id, ['role' => 'member']);
        $group->members()->attach($otherUser->id, ['role' => 'member']);

        $message = Message::factory()->create([
            'conversation_id' => $group->id,
            'sender_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/messages/{$message->id}/read");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('message_reads', [
            'message_id' => $message->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_mark_all_messages_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $group = Conversation::factory()->create(['type' => 'group']);
        $group->members()->attach($user->id, ['role' => 'member']);
        $group->members()->attach($otherUser->id, ['role' => 'member']);

        // Create multiple unread messages from otherUser
        $messages = Message::factory()->count(3)->create([
            'conversation_id' => $group->id,
            'sender_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/conversations/{$group->id}/read-all");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        foreach ($messages as $msg) {
            $this->assertDatabaseHas('message_reads', [
                'message_id' => $msg->id,
                'user_id' => $user->id,
            ]);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_file_report_against_post(): void
    {
        $reporter = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'Post',
                'reportable_id' => $post->id,
                'reason' => 'Konten tidak senonoh.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reportable_id', $post->id)
            ->assertJsonPath('data.reportable_type', 'Post');

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => 'App\\Models\\Post',
            'reportable_id' => $post->id,
            'reason' => 'Konten tidak senonoh.',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_list_and_resolve_reports_while_students_are_blocked(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();

        $report = Report::create([
            'reporter_id' => $student->id,
            'reportable_type' => 'App\\Models\\Post',
            'reportable_id' => $post->id,
            'reason' => 'Inappropriate content',
            'status' => 'pending',
        ]);

        // 1. Verify student cannot list reports
        $responseStudentList = $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/admin/reports');
        $responseStudentList->assertStatus(403);

        // 2. Verify admin can list reports
        $responseAdminList = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/reports');
        $responseAdminList->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // 3. Verify student cannot resolve report
        $responseStudentResolve = $this->actingAs($student, 'sanctum')
            ->putJson("/api/v1/admin/reports/{$report->id}/resolve");
        $responseStudentResolve->assertStatus(403);

        // 4. Verify admin can resolve report
        $responseAdminResolve = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/reports/{$report->id}/resolve");
        $responseAdminResolve->assertStatus(200);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'resolved',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_user_can_register_device_token(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/device-tokens', [
                'token' => 'fcm-token-123456',
                'platform' => 'android',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token-123456',
            'platform' => 'android',
        ]);
    }

    public function test_user_can_manage_notifications(): void
    {
        $user = User::factory()->create();

        // Create mock database notification
        $notification = $user->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => 'message',
            'data' => ['body' => 'Hello test notification'],
        ]);

        // 1. Get notifications list
        $responseList = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications');

        $responseList->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_read', false);

        // 2. Mark single notification read
        $responseRead = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/notifications/{$notification->id}/read");
        $responseRead->assertStatus(200);

        $this->assertNotNull($notification->fresh()->read_at);

        // Reset notification as unread
        $notification->update(['read_at' => null]);

        // 3. Mark all notifications read
        $responseReadAll = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/notifications/read-all');
        $responseReadAll->assertStatus(200);

        $this->assertNotNull($notification->fresh()->read_at);
    }
}

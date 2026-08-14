<?php

namespace Tests\Feature;

use App\Models\CustomNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->customer()->create();
    }

    private function createNotificationFor(User $user): CustomNotification
    {
        return CustomNotification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Test Notification',
            'message' => 'Test message',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([]),
        ]);
    }

    public function test_user_can_view_notifications(): void
    {
        $this->createNotificationFor($this->customer);

        $token = $this->customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_user_can_get_unread_count(): void
    {
        $this->createNotificationFor($this->customer);

        $token = $this->customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/notifications/unread-count');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'unread_count' => 1,
                ],
            ]);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $notification = $this->createNotificationFor($this->customer);

        $token = $this->customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/notifications/' . $notification->id . '/read');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Notification marked as read.',
            ]);
    }

    public function test_user_can_mark_all_as_read(): void
    {
        $this->createNotificationFor($this->customer);

        CustomNotification::create([
            'user_id' => $this->customer->id,
            'type' => 'test2',
            'title' => 'Test 2',
            'message' => 'Message 2',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->customer->id,
            'data' => json_encode([]),
        ]);

        $token = $this->customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/notifications/read-all');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'All notifications marked as read.',
            ]);

        $unreadCount = CustomNotification::where('user_id', $this->customer->id)
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(0, $unreadCount);
    }

    public function test_user_cannot_view_other_users_notifications(): void
    {
        $otherUser = User::factory()->customer()->create();

        CustomNotification::create([
            'user_id' => $otherUser->id,
            'type' => 'private',
            'title' => 'Private Notification',
            'message' => 'Private message',
            'notifiable_type' => User::class,
            'notifiable_id' => $otherUser->id,
            'data' => json_encode([]),
        ]);

        $token = $this->customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/notifications');

        $response->assertOk();

        $data = $response->json('data.data');
        $this->assertIsArray($data);
        $this->assertCount(0, $data);
    }
}

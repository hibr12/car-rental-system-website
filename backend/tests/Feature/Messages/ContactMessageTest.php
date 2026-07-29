<?php

namespace Tests\Feature\Messages;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private string $adminToken;
    private string $customerToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->customer()->create();

        $this->adminToken = $this->admin->createToken('auth-token')->plainTextToken;
        $this->customerToken = $this->customer->createToken('auth-token')->plainTextToken;
    }

    public function test_public_user_can_submit_contact_message(): void
    {
        $response = $this->postJson('/api/contact-messages', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'subject' => 'Inquiry about rental',
            'message' => 'I would like to know more about your services.',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Contact message submitted successfully',
            ]);

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_contact_message_validation_works(): void
    {
        $response = $this->postJson('/api/contact-messages', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);
    }

    public function test_admin_can_list_contact_messages(): void
    {
        ContactMessage::factory()->count(5)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/contact-messages');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta',
            ]);
    }

    public function test_customer_cannot_list_contact_messages(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->customerToken)
            ->getJson('/api/contact-messages');

        $response->assertStatus(403);
    }

    public function test_admin_can_update_contact_message_status(): void
    {
        $message = ContactMessage::factory()->create(['status' => 'pending']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson('/api/contact-messages/' . $message->id, ['status' => 'read']);

        $response->assertOk();
        $this->assertDatabaseHas('contact_messages', ['id' => $message->id, 'status' => 'read']);
    }

    public function test_admin_can_mark_message_as_replied_with_timestamp(): void
    {
        $message = ContactMessage::factory()->create(['status' => 'pending']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson('/api/contact-messages/' . $message->id, ['status' => 'replied']);

        $response->assertOk();
        $message->refresh();
        $this->assertSame('replied', $message->status);
        $this->assertNotNull($message->replied_at);
    }

    public function test_admin_can_delete_contact_message(): void
    {
        $message = ContactMessage::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson('/api/contact-messages/' . $message->id);

        $response->assertOk();
        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
    }

    public function test_customer_cannot_delete_contact_message(): void
    {
        $message = ContactMessage::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->customerToken)
            ->deleteJson('/api/contact-messages/' . $message->id);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_list_contact_messages(): void
    {
        $response = $this->getJson('/api/contact-messages');
        $response->assertStatus(401);
    }

    public function test_contact_message_filter_by_status(): void
    {
        ContactMessage::factory()->count(3)->create(['status' => 'pending']);
        ContactMessage::factory()->count(2)->create(['status' => 'replied']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/contact-messages?status=pending');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }
}

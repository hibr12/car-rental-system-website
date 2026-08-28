<?php

namespace Tests\Feature\Vehicles;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_view_categories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);

        $response->assertJsonFragment(['success' => true]);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_anyone_can_view_category_details(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
            ]);
    }

    public function test_admin_can_create_category(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/categories', [
                'name' => 'New Category',
                'description' => 'A new test category',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'slug'],
            ]);

        $this->assertDatabaseHas('categories', ['slug' => 'new-category']);
    }

    public function test_category_creation_validates_unique_name(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('auth-token')->plainTextToken;
        Category::factory()->create(['name' => 'Existing Category']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/categories', [
                'name' => 'Existing Category',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_update_category(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('auth-token')->plainTextToken;
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/categories/{$category->id}", [
                'name' => 'Updated Category',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'name' => 'Updated Category',
                ],
            ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('auth-token')->plainTextToken;
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}

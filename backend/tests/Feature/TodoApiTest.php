<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_todo_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $todo = Todo::create([
            'user_id' => $user->id,
            'title' => 'Owned todo',
            'description' => null,
            'is_completed' => false,
        ]);

        $this->assertTrue($todo->user->is($user));
    }

    public function test_user_has_many_todos(): void
    {
        $user = User::factory()->create();

        Todo::create([
            'user_id' => $user->id,
            'title' => 'First todo',
            'description' => null,
            'is_completed' => false,
        ]);

        Todo::create([
            'user_id' => $user->id,
            'title' => 'Second todo',
            'description' => null,
            'is_completed' => false,
        ]);

        $this->assertCount(2, $user->todos);
    }

    public function test_guest_cannot_access_todo_routes(): void
    {
        $this->getJson('/api/todos')->assertUnauthorized();

        $this->postJson('/api/todos', [
            'title' => 'Guest todo',
        ])->assertUnauthorized();
    }

    public function test_user_only_sees_their_own_todos(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Todo::create([
            'user_id' => $user->id,
            'title' => 'Mine',
            'description' => null,
            'is_completed' => false,
        ]);

        Todo::create([
            'user_id' => $otherUser->id,
            'title' => 'Not mine',
            'description' => null,
            'is_completed' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/todos')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Mine'])
            ->assertJsonMissing(['title' => 'Not mine']);
    }

    public function test_created_todo_belongs_to_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/todos', [
                'title' => 'My new todo',
                'description' => null,
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'My new todo');

        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => 'My new todo',
        ]);
    }
}

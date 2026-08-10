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
}

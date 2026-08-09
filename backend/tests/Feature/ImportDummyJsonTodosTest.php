<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;


use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Todo;
use Tests\TestCase;
use App\Models\TodoImport;
use Illuminate\Support\Facades\Queue;

class ImportDummyJsonTodosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_it_imports_todos_from_dummyjson(): void
    {
        Http::fake([
            'https://dummyjson.com/todos' => Http::response([
                'todos' => [
                    [
                        'id' => 1,
                        'todo' => 'Learn Laravel testing',
                        'completed' => false,
                        'userId' => 10,
                    ],
                ],
            ], 200),
        ]);
        $response = $this->postJson('/api/integrations/dummy-json/todos/import');

        // dd($response->json());

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 200,
                    'imported' => 1,
                    'source' => 'dummyjson',
                ],
            ]);

        $this->assertDatabaseHas('todos', [
            Todo::FIELD_SOURCE => 'dummyjson',
            Todo::FIELD_EXTERNAL_ID => 1,
            Todo::FIELD_TITLE => 'Learn Laravel testing',
            Todo::FIELD_DESCRIPTION => '',
            Todo::FIELD_IS_COMPLETED => false,
        ]);
    }

    public function test_it_updates_existing_dummyjson_todos_without_creating_duplicates(): void
    {
        Http::fake([
            'https://dummyjson.com/todos' => Http::sequence()
                ->push([
                    'todos' => [
                        [
                            'id' => 1,
                            'todo' => 'Original title',
                            'completed' => false,
                            'userId' => 10,
                        ],
                    ],
                ], 200)
                ->push([
                    'todos' => [
                        [
                            'id' => 1,
                            'todo' => 'Updated title',
                            'completed' => true,
                            'userId' => 10,
                        ],
                    ],
                ], 200),
        ]);

        $this->postJson('/api/integrations/dummy-json/todos/import')->assertOk();
        $this->postJson('/api/integrations/dummy-json/todos/import')->assertOk();

        $this->assertDatabaseCount('todos', 1);

        $this->assertDatabaseHas('todos', [
            Todo::FIELD_SOURCE => 'dummyjson',
            Todo::FIELD_EXTERNAL_ID => 1,
            Todo::FIELD_TITLE => 'Updated title',
            Todo::FIELD_IS_COMPLETED => true,
        ]);
    }

    public function test_it_returns_bad_gateway_when_dummyjson_fails(): void
    {
        Http::fake([
            'https://dummyjson.com/todos' => Http::response([], 500),
        ]);

        $response = $this->postJson('/api/integrations/dummy-json/todos/import');

        $response
            ->assertStatus(502)
            ->assertJson([
                'message' => 'Failed to fetch todos from DummyJSON.',
            ]);

        $this->assertDatabaseCount('todos', 0);
    }
    public function test_import_endpoint_creates_pending_import_record(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/integrations/dummy-json/todos/import');

        $response
            ->assertAccepted()
            ->assertJson([
                'data' => [
                    'source' => 'dummyjson',
                    'status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('todo_imports', [
            'source' => 'dummyjson',
            'status' => 'pending',
            'imported_count' => 0,
            'error_message' => null,
        ]);
    }
}

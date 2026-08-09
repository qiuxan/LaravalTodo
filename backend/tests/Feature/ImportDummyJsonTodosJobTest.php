<?php

namespace Tests\Feature;

use App\Jobs\ImportDummyJsonTodosJob;
use App\Models\Todo;
use App\Models\TodoImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Actions\Todos\ImportDummyJsonTodosAction;

class ImportDummyJsonTodosJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_imports_todos_and_marks_import_completed(): void
    {
        Http::fake([
            'https://dummyjson.com/todos' => Http::response([
                'todos' => [
                    [
                        'id' => 1,
                        'todo' => 'Queued import todo',
                        'completed' => false,
                        'userId' => 10,
                    ],
                ],
            ], 200),
        ]);

        $import = TodoImport::create([
            'source' => 'dummyjson',
            'status' => TodoImport::STATUS_PENDING,
            'imported_count' => 0,
        ]);

        (new ImportDummyJsonTodosJob($import))->handle(
            app(ImportDummyJsonTodosAction::class)
        );

        $this->assertDatabaseHas('todos', [
            Todo::FIELD_SOURCE => 'dummyjson',
            Todo::FIELD_EXTERNAL_ID => 1,
            Todo::FIELD_TITLE => 'Queued import todo',
            Todo::FIELD_IS_COMPLETED => false,
        ]);

        $this->assertDatabaseHas('todo_imports', [
            'id' => $import->id,
            'source' => 'dummyjson',
            'status' => TodoImport::STATUS_COMPLETED,
            'imported_count' => 1,
            'error_message' => null,
        ]);
    }

    public function test_job_updates_existing_dummyjson_todos_without_creating_duplicates(): void
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

        $firstImport = TodoImport::create([
            'source' => 'dummyjson',
            'status' => TodoImport::STATUS_PENDING,
            'imported_count' => 0,
        ]);

        $secondImport = TodoImport::create([
            'source' => 'dummyjson',
            'status' => TodoImport::STATUS_PENDING,
            'imported_count' => 0,
        ]);

        (new ImportDummyJsonTodosJob($firstImport))->handle(
            app(ImportDummyJsonTodosAction::class)
        );

        (new ImportDummyJsonTodosJob($secondImport))->handle(
            app(ImportDummyJsonTodosAction::class)
        );

        $this->assertDatabaseCount('todos', 1);

        $this->assertDatabaseHas('todos', [
            Todo::FIELD_SOURCE => 'dummyjson',
            Todo::FIELD_EXTERNAL_ID => 1,
            Todo::FIELD_TITLE => 'Updated title',
            Todo::FIELD_IS_COMPLETED => true,
        ]);

        $this->assertDatabaseHas('todo_imports', [
            'id' => $secondImport->id,
            'status' => TodoImport::STATUS_COMPLETED,
            'imported_count' => 1,
        ]);
    }

    public function test_job_marks_import_failed_when_dummyjson_fails(): void
    {
        Http::fake([
            'https://dummyjson.com/todos' => Http::response([], 500),
        ]);

        $import = TodoImport::create([
            'source' => 'dummyjson',
            'status' => TodoImport::STATUS_PENDING,
            'imported_count' => 0,
        ]);

        try {
            (new ImportDummyJsonTodosJob($import))->handle(
                app(\App\Actions\Todos\ImportDummyJsonTodosAction::class)
            );
        } catch (\RuntimeException) {
        }

        $this->assertDatabaseHas('todo_imports', [
            'id' => $import->id,
            'status' => TodoImport::STATUS_FAILED,
            'imported_count' => 0,
            'error_message' => 'Failed to fetch todos from DummyJSON.',
        ]);

        $this->assertDatabaseCount('todos', 0);
    }
}

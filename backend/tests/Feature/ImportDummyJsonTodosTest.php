<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\TodoImport;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ImportDummyJsonTodosJob;

class ImportDummyJsonTodosTest extends TestCase
{
    use RefreshDatabase;

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
    public function test_import_endpoint_dispatches_import_job(): void
    {
        Queue::fake();

        $this->postJson('/api/integrations/dummy-json/todos/import')
            ->assertAccepted();

        Queue::assertPushed(ImportDummyJsonTodosJob::class);
    }
    public function test_it_returns_import_status(): void
    {
        $import = TodoImport::create([
            'source' => 'dummyjson',
            'status' => TodoImport::STATUS_COMPLETED,
            'imported_count' => 30,
            'error_message' => null,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        $this->getJson("/api/integrations/dummy-json/todos/imports/{$import->id}")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $import->id,
                    'source' => 'dummyjson',
                    'status' => TodoImport::STATUS_COMPLETED,
                    'imported_count' => 30,
                    'error_message' => null,
                ],
            ]);
    }
}

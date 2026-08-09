# Queued DummyJSON Todo Import Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the DummyJSON todo import from a synchronous HTTP request into a Laravel queue job with a status endpoint.

**Architecture:** The import endpoint creates a business-level `TodoImport` record and dispatches a queue job. The job performs the existing DummyJSON fetch and database upsert, then updates the import record to `completed` or `failed`. A separate GET endpoint returns the import status.

**Tech Stack:** Laravel 13, MariaDB, Laravel database queue, Laravel Jobs, Feature tests with `Http::fake()` and queue fakes where appropriate.

## Global Constraints

- Keep the existing Todo CRUD API working.
- Keep `ImportDummyJsonTodosAction` responsible for the actual third-party fetch and todo upsert.
- Do not use Redis for this exercise; use Laravel's `database` queue connection.
- Do not use Laravel's internal `jobs` table as the public business status source.
- Track business import state in a separate `todo_imports` table.
- Trigger endpoint should return `202 Accepted` once the job is queued.
- Status endpoint should return the current state of one import.
- Tests must use `laravel_todo_test`, not the development database.

---

## File Structure

- Modify `backend/.env`: set `QUEUE_CONNECTION=database` for local development.
- Verify `backend/database/migrations/0001_01_01_000002_create_jobs_table.php`: Laravel queue tables already exist in this project.
- Create `backend/app/Models/TodoImport.php`: Eloquent model for business import status.
- Create `backend/database/migrations/*_create_todo_imports_table.php`: stores source, status, count, error, and timestamps.
- Create `backend/app/Jobs/ImportDummyJsonTodosJob.php`: runs the actual import in the queue worker.
- Modify `backend/app/Http/Controllers/DummyJsonTodoImportController.php`: dispatches the job instead of running the import inline.
- Create `backend/app/Http/Controllers/DummyJsonTodoImportStatusController.php`: returns one import status.
- Modify `backend/routes/api.php`: adds `GET /api/integrations/dummy-json/todos/imports/{todoImport}`.
- Modify `backend/tests/Feature/ImportDummyJsonTodosTest.php`: update endpoint tests for async behavior.
- Create `backend/tests/Feature/ImportDummyJsonTodosJobTest.php`: tests job success and failure behavior.
- Modify `postman/laravel-todo-api.postman_collection.json`: add trigger import and check import status requests.

---

### Task 1: Add TodoImport Status Model

**Files:**
- Create: `backend/app/Models/TodoImport.php`
- Create: `backend/database/migrations/*_create_todo_imports_table.php`
- Test: `backend/tests/Feature/ImportDummyJsonTodosTest.php`

**Interfaces:**
- Produces model: `App\Models\TodoImport`
- Produces fields: `source`, `status`, `imported_count`, `error_message`, `started_at`, `finished_at`
- Status values: `pending`, `running`, `completed`, `failed`

- [ ] **Step 1: Generate model and migration**

Run from `backend/`:

```bash
php artisan make:model TodoImport -m
```

- [ ] **Step 2: Write failing test for creating an import record**

Add this test to `backend/tests/Feature/ImportDummyJsonTodosTest.php`:

```php
use App\Models\TodoImport;
use Illuminate\Support\Facades\Queue;
```

```php
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
```

- [ ] **Step 3: Run test to verify it fails**

```bash
php artisan test --filter=test_import_endpoint_creates_pending_import_record
```

Expected: FAIL because `todo_imports` table or model/controller behavior does not exist yet.

- [ ] **Step 4: Edit migration**

In the generated migration:

```php
Schema::create('todo_imports', function (Blueprint $table) {
    $table->id();
    $table->string('source');
    $table->string('status');
    $table->unsignedInteger('imported_count')->default(0);
    $table->text('error_message')->nullable();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('finished_at')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 5: Edit TodoImport model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodoImport extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'source',
        'status',
        'imported_count',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 6: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 7: Temporarily update controller enough to pass creation test**

In `DummyJsonTodoImportController`, create a pending import and return `202`:

```php
use App\Models\TodoImport;
```

```php
public function __invoke(): JsonResponse
{
    $import = TodoImport::create([
        'source' => 'dummyjson',
        'status' => TodoImport::STATUS_PENDING,
        'imported_count' => 0,
    ]);

    return response()->json([
        'data' => [
            'id' => $import->id,
            'source' => $import->source,
            'status' => $import->status,
        ],
    ], 202);
}
```

- [ ] **Step 8: Run test to verify it passes**

```bash
php artisan test --filter=test_import_endpoint_creates_pending_import_record
```

Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add backend/app/Models/TodoImport.php backend/database/migrations backend/app/Http/Controllers/DummyJsonTodoImportController.php backend/tests/Feature/ImportDummyJsonTodosTest.php
git commit -m "feat: track todo import status"
```

---

### Task 2: Dispatch Import Job From Endpoint

**Files:**
- Create: `backend/app/Jobs/ImportDummyJsonTodosJob.php`
- Modify: `backend/app/Http/Controllers/DummyJsonTodoImportController.php`
- Test: `backend/tests/Feature/ImportDummyJsonTodosTest.php`

**Interfaces:**
- Consumes: `TodoImport $todoImport`
- Produces job: `App\Jobs\ImportDummyJsonTodosJob`
- Endpoint behavior: POST returns `202` and queues one job.

- [ ] **Step 1: Generate job**

```bash
php artisan make:job ImportDummyJsonTodosJob
```

- [ ] **Step 2: Write failing dispatch test**

Add to `ImportDummyJsonTodosTest.php`:

```php
use App\Jobs\ImportDummyJsonTodosJob;
```

```php
public function test_import_endpoint_dispatches_import_job(): void
{
    Queue::fake();

    $this->postJson('/api/integrations/dummy-json/todos/import')
        ->assertAccepted();

    Queue::assertPushed(ImportDummyJsonTodosJob::class);
}
```

- [ ] **Step 3: Run test to verify it fails**

```bash
php artisan test --filter=test_import_endpoint_dispatches_import_job
```

Expected: FAIL because the controller has not dispatched the job yet.

- [ ] **Step 4: Implement job constructor**

In `ImportDummyJsonTodosJob.php`:

```php
<?php

namespace App\Jobs;

use App\Models\TodoImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportDummyJsonTodosJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TodoImport $todoImport
    ) {
    }

    public function handle(): void
    {
    }
}
```

- [ ] **Step 5: Dispatch job from controller**

In `DummyJsonTodoImportController`:

```php
use App\Jobs\ImportDummyJsonTodosJob;
```

After creating `$import`:

```php
ImportDummyJsonTodosJob::dispatch($import);
```

- [ ] **Step 6: Run endpoint tests**

```bash
php artisan test --filter=ImportDummyJsonTodosTest
```

Expected: updated endpoint tests pass.

- [ ] **Step 7: Commit**

```bash
git add backend/app/Jobs/ImportDummyJsonTodosJob.php backend/app/Http/Controllers/DummyJsonTodoImportController.php backend/tests/Feature/ImportDummyJsonTodosTest.php
git commit -m "feat: dispatch dummyjson import job"
```

---

### Task 3: Move Import Execution Into Job

**Files:**
- Modify: `backend/app/Jobs/ImportDummyJsonTodosJob.php`
- Test: `backend/tests/Feature/ImportDummyJsonTodosJobTest.php`

**Interfaces:**
- Consumes action: `ImportDummyJsonTodosAction::handle(): array`
- Expects action result keys: `imported`, `source`
- Updates `TodoImport.status`, `imported_count`, `error_message`, `started_at`, `finished_at`

- [ ] **Step 1: Create job test file**

```bash
php artisan make:test ImportDummyJsonTodosJobTest
```

- [ ] **Step 2: Write failing success test**

In `ImportDummyJsonTodosJobTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Jobs\ImportDummyJsonTodosJob;
use App\Models\Todo;
use App\Models\TodoImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

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

        (new ImportDummyJsonTodosJob($import))->handle();

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
}
```

- [ ] **Step 3: Run test to verify it fails**

```bash
php artisan test --filter=test_job_imports_todos_and_marks_import_completed
```

Expected: FAIL because job `handle()` does not call the action yet.

- [ ] **Step 4: Implement job success path**

In `ImportDummyJsonTodosJob.php`:

```php
use App\Actions\Todos\ImportDummyJsonTodosAction;
use Throwable;
```

```php
public function handle(ImportDummyJsonTodosAction $action): void
{
    $this->todoImport->update([
        'status' => TodoImport::STATUS_RUNNING,
        'started_at' => now(),
    ]);

    try {
        $result = $action->handle();

        $this->todoImport->update([
            'status' => TodoImport::STATUS_COMPLETED,
            'imported_count' => $result['imported'],
            'error_message' => null,
            'finished_at' => now(),
        ]);
    } catch (Throwable $exception) {
        $this->todoImport->update([
            'status' => TodoImport::STATUS_FAILED,
            'error_message' => $exception->getMessage(),
            'finished_at' => now(),
        ]);

        throw $exception;
    }
}
```

- [ ] **Step 5: Run success test**

```bash
php artisan test --filter=test_job_imports_todos_and_marks_import_completed
```

Expected: PASS.

- [ ] **Step 6: Write failing failure test**

Add:

```php
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
        app(ImportDummyJsonTodosJob::class, ['todoImport' => $import])->handle(
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
```

- [ ] **Step 7: Run job tests**

```bash
php artisan test --filter=ImportDummyJsonTodosJobTest
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add backend/app/Jobs/ImportDummyJsonTodosJob.php backend/tests/Feature/ImportDummyJsonTodosJobTest.php
git commit -m "feat: run dummyjson import in queued job"
```

---

### Task 4: Add Import Status Endpoint

**Files:**
- Create: `backend/app/Http/Controllers/DummyJsonTodoImportStatusController.php`
- Modify: `backend/routes/api.php`
- Test: `backend/tests/Feature/ImportDummyJsonTodosTest.php`

**Interfaces:**
- Produces route: `GET /api/integrations/dummy-json/todos/imports/{todoImport}`
- Uses route model binding: `TodoImport $todoImport`

- [ ] **Step 1: Write failing status endpoint test**

Add:

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=test_it_returns_import_status
```

Expected: FAIL with 404 because route/controller does not exist yet.

- [ ] **Step 3: Generate controller**

```bash
php artisan make:controller DummyJsonTodoImportStatusController
```

- [ ] **Step 4: Implement controller**

```php
<?php

namespace App\Http\Controllers;

use App\Models\TodoImport;
use Illuminate\Http\JsonResponse;

class DummyJsonTodoImportStatusController extends Controller
{
    public function __invoke(TodoImport $todoImport): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $todoImport->id,
                'source' => $todoImport->source,
                'status' => $todoImport->status,
                'imported_count' => $todoImport->imported_count,
                'error_message' => $todoImport->error_message,
                'started_at' => $todoImport->started_at,
                'finished_at' => $todoImport->finished_at,
            ],
        ]);
    }
}
```

- [ ] **Step 5: Add route**

In `routes/api.php`:

```php
use App\Http\Controllers\DummyJsonTodoImportStatusController;
```

```php
Route::get(
    'integrations/dummy-json/todos/imports/{todoImport}',
    DummyJsonTodoImportStatusController::class
);
```

- [ ] **Step 6: Run status test**

```bash
php artisan test --filter=test_it_returns_import_status
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add backend/app/Http/Controllers/DummyJsonTodoImportStatusController.php backend/routes/api.php backend/tests/Feature/ImportDummyJsonTodosTest.php
git commit -m "feat: expose todo import status endpoint"
```

---

### Task 5: Configure Queue Worker And Manual Verification

**Files:**
- Modify: `backend/.env`
- Modify: `postman/laravel-todo-api.postman_collection.json`

**Interfaces:**
- Queue driver: `QUEUE_CONNECTION=database`
- Worker command: `php artisan queue:work`

- [ ] **Step 1: Set local queue connection**

In `backend/.env`:

```env
QUEUE_CONNECTION=database
```

- [ ] **Step 2: Confirm queue tables exist**

```bash
php artisan migrate:status | grep jobs
```

Expected: jobs migrations are `Ran`.

- [ ] **Step 3: Run full automated tests**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 4: Start queue worker**

In one terminal from `backend/`:

```bash
php artisan queue:work
```

- [ ] **Step 5: Trigger import manually**

In another terminal:

```bash
curl -i -X POST http://127.0.0.1:8000/api/integrations/dummy-json/todos/import \
  -H "Accept: application/json"
```

Expected:

```http
HTTP/1.1 202 Accepted
```

Response shape:

```json
{
  "data": {
    "id": 1,
    "source": "dummyjson",
    "status": "pending"
  }
}
```

- [ ] **Step 6: Check import status**

Use the returned `id`:

```bash
curl -i http://127.0.0.1:8000/api/integrations/dummy-json/todos/imports/1 \
  -H "Accept: application/json"
```

Expected after worker runs:

```json
{
  "data": {
    "id": 1,
    "source": "dummyjson",
    "status": "completed",
    "imported_count": 30,
    "error_message": null
  }
}
```

- [ ] **Step 7: Add Postman requests**

Add two requests to the existing collection:

```text
POST {{base_url}}/api/integrations/dummy-json/todos/import
GET {{base_url}}/api/integrations/dummy-json/todos/imports/{{import_id}}
```

- [ ] **Step 8: Commit**

```bash
git add backend/.env postman/laravel-todo-api.postman_collection.json
git commit -m "chore: document queued import workflow"
```

---

## Self-Review Notes

- The plan keeps third-party fetch/upsert logic inside `ImportDummyJsonTodosAction`.
- The HTTP trigger endpoint no longer blocks on the third-party API.
- `todo_imports` is the business status table; Laravel's `jobs` table remains internal queue infrastructure.
- Tests are split by behavior: endpoint queuing, job execution, and status lookup.
- The plan uses MariaDB-compatible tests because the import action uses MariaDB raw SQL.

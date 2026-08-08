# DummyJSON Todo Import Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Laravel endpoint that imports todos from `https://dummyjson.com/todos` into the local `todos` table without creating duplicates.

**Architecture:** Keep the existing Laravel structure. A dedicated integration route calls a controller, the controller calls an action, the action uses Laravel's HTTP client to fetch DummyJSON data and maps remote todos into local Eloquent `Todo` records with idempotent upsert behavior.

**Tech Stack:** Laravel 13, Laravel HTTP Client, Eloquent, MariaDB, FormRequest-free import endpoint, curl/Postman for manual testing, Laravel feature tests with `Http::fake()`.

## Global Constraints

- Keep the existing Todo CRUD API working.
- Keep the existing Action pattern under `app/Actions/Todos`.
- Do not add a Repository layer for this exercise.
- Do not expose DummyJSON fields directly to the frontend.
- Import must be idempotent: running it multiple times must not duplicate remote todos.
- Use `source = dummyjson` and `external_id = remote id` to identify imported rows.
- The local Todo API response shape remains controlled by `TodoResource`.

---

## File Structure

- Modify `database/migrations/*_create_todos_table.php` only if the migration has not been shared or committed; otherwise create a new migration.
- Create `database/migrations/*_add_external_fields_to_todos_table.php`: Adds `source` and `external_id`.
- Modify `app/Models/Todo.php`: Adds `source` and `external_id` to `$fillable`.
- Create `app/Actions/Todos/ImportDummyJsonTodosAction.php`: Fetches DummyJSON todos and upserts local todos.
- Create `app/Http/Controllers/DummyJsonTodoImportController.php`: Handles the import HTTP endpoint.
- Modify `routes/api.php`: Adds `POST /api/integrations/dummy-json/todos/import`.
- Create `tests/Feature/ImportDummyJsonTodosTest.php`: Tests import success, mapping, idempotency, and third-party failure.
- Modify `postman/laravel-todo-api.postman_collection.json`: Adds an import request.

---

### Task 1: Add External Import Fields

**Files:**
- Create: `backend/database/migrations/*_add_external_fields_to_todos_table.php`
- Modify: `backend/app/Models/Todo.php`

**Interfaces:**
- Produces local uniqueness identity: `source + external_id`.

- [ ] **Step 1: Generate migration**

Run from `backend/`:

```bash
php artisan make:migration add_external_fields_to_todos_table --table=todos
```

- [ ] **Step 2: Edit migration**

In `up()`:

```php
Schema::table('todos', function (Blueprint $table) {
    $table->string('source')->nullable()->after('id');
    $table->unsignedInteger('external_id')->nullable()->after('source');
    $table->unique(['source', 'external_id']);
});
```

In `down()`:

```php
Schema::table('todos', function (Blueprint $table) {
    $table->dropUnique(['source', 'external_id']);
    $table->dropColumn(['source', 'external_id']);
});
```

- [ ] **Step 3: Update Todo model fillable**

Add fields:

```php
protected $fillable = [
    'source',
    'external_id',
    'title',
    'description',
    'is_completed',
];
```

- [ ] **Step 4: Run migration**

```bash
php artisan migrate
```

Expected: `todos` has nullable `source`, nullable `external_id`, and a unique index.

---

### Task 2: Write Import Action

**Files:**
- Create: `backend/app/Actions/Todos/ImportDummyJsonTodosAction.php`

**Interfaces:**
- Consumes DummyJSON response shape:

```json
{
  "todos": [
    {
      "id": 1,
      "todo": "Do something nice",
      "completed": false,
      "userId": 152
    }
  ],
  "total": 254,
  "skip": 0,
  "limit": 30
}
```

- Produces summary array:

```php
[
    'imported' => 30,
    'source' => 'dummyjson',
]
```

- [ ] **Step 1: Create action**

```php
<?php

namespace App\Actions\Todos;

use App\Models\Todo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ImportDummyJsonTodosAction
{
    public function handle(): array
    {
        $response = Http::timeout(10)->get('https://dummyjson.com/todos');

        if (! $response->successful()) {
            throw new RuntimeException('Failed to fetch todos from DummyJSON.');
        }

        $remoteTodos = $response->json('todos', []);

        DB::transaction(function () use ($remoteTodos): void {
            foreach ($remoteTodos as $remoteTodo) {
                Todo::updateOrCreate(
                    [
                        'source' => 'dummyjson',
                        'external_id' => $remoteTodo['id'],
                    ],
                    [
                        'title' => $remoteTodo['todo'],
                        'description' => 'Imported from DummyJSON user '.$remoteTodo['userId'],
                        'is_completed' => $remoteTodo['completed'],
                    ]
                );
            }
        });

        return [
            'imported' => count($remoteTodos),
            'source' => 'dummyjson',
        ];
    }
}
```

- [ ] **Step 2: Syntax check**

```bash
php -l app/Actions/Todos/ImportDummyJsonTodosAction.php
```

Expected: no syntax errors.

---

### Task 3: Add Import Endpoint

**Files:**
- Create: `backend/app/Http/Controllers/DummyJsonTodoImportController.php`
- Modify: `backend/routes/api.php`

**Interfaces:**
- Produces: `POST /api/integrations/dummy-json/todos/import`

- [ ] **Step 1: Generate controller**

```bash
php artisan make:controller DummyJsonTodoImportController
```

- [ ] **Step 2: Implement controller**

```php
<?php

namespace App\Http\Controllers;

use App\Actions\Todos\ImportDummyJsonTodosAction;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class DummyJsonTodoImportController extends Controller
{
    public function __invoke(ImportDummyJsonTodosAction $action): JsonResponse
    {
        try {
            return response()->json([
                'data' => $action->handle(),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        }
    }
}
```

- [ ] **Step 3: Add route**

In `routes/api.php`:

```php
use App\Http\Controllers\DummyJsonTodoImportController;

Route::post(
    'integrations/dummy-json/todos/import',
    DummyJsonTodoImportController::class
);
```

- [ ] **Step 4: Verify route**

```bash
php artisan route:list --path=api/integrations
```

Expected:

```text
POST api/integrations/dummy-json/todos/import
```

---

### Task 4: Feature Tests With Fake Third-Party API

**Files:**
- Create: `backend/tests/Feature/ImportDummyJsonTodosTest.php`

**Interfaces:**
- Consumes route from Task 3.
- Verifies database rows and response status.

- [ ] **Step 1: Create test file**

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportDummyJsonTodosTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_dummy_json_todos(): void
    {
        Http::fake([
            'dummyjson.com/todos' => Http::response([
                'todos' => [
                    [
                        'id' => 1,
                        'todo' => 'Import one todo',
                        'completed' => false,
                        'userId' => 10,
                    ],
                ],
                'total' => 1,
                'skip' => 0,
                'limit' => 1,
            ]),
        ]);

        $this->postJson('/api/integrations/dummy-json/todos/import')
            ->assertOk()
            ->assertJsonPath('data.imported', 1)
            ->assertJsonPath('data.source', 'dummyjson');

        $this->assertDatabaseHas('todos', [
            'source' => 'dummyjson',
            'external_id' => 1,
            'title' => 'Import one todo',
            'description' => 'Imported from DummyJSON user 10',
            'is_completed' => false,
        ]);
    }

    public function test_import_is_idempotent(): void
    {
        Http::fake([
            'dummyjson.com/todos' => Http::response([
                'todos' => [
                    [
                        'id' => 1,
                        'todo' => 'Import one todo',
                        'completed' => true,
                        'userId' => 10,
                    ],
                ],
                'total' => 1,
                'skip' => 0,
                'limit' => 1,
            ]),
        ]);

        $this->postJson('/api/integrations/dummy-json/todos/import')->assertOk();
        $this->postJson('/api/integrations/dummy-json/todos/import')->assertOk();

        $this->assertDatabaseCount('todos', 1);
        $this->assertDatabaseHas('todos', [
            'source' => 'dummyjson',
            'external_id' => 1,
            'is_completed' => true,
        ]);
    }

    public function test_it_returns_bad_gateway_when_dummy_json_fails(): void
    {
        Http::fake([
            'dummyjson.com/todos' => Http::response([], 500),
        ]);

        $this->postJson('/api/integrations/dummy-json/todos/import')
            ->assertStatus(502)
            ->assertJsonPath('message', 'Failed to fetch todos from DummyJSON.');
    }
}
```

- [ ] **Step 2: Run failing test before implementation if using TDD**

```bash
php artisan test --filter=ImportDummyJsonTodosTest
```

Expected before implementation: failures for missing route/class.

- [ ] **Step 3: Run test after implementation**

```bash
php artisan test --filter=ImportDummyJsonTodosTest
```

Expected after implementation: all tests pass.

---

### Task 5: Manual Test With Real DummyJSON

**Files:**
- Modify: `postman/laravel-todo-api.postman_collection.json`

**Interfaces:**
- Consumes running Laravel API at `http://127.0.0.1:8000`.

- [ ] **Step 1: Start services**

```bash
docker compose up -d
cd backend
php artisan serve
```

- [ ] **Step 2: Import real todos**

```bash
curl -i -X POST http://127.0.0.1:8000/api/integrations/dummy-json/todos/import \
  -H "Accept: application/json"
```

Expected:

```json
{
  "data": {
    "imported": 30,
    "source": "dummyjson"
  }
}
```

- [ ] **Step 3: Verify local list**

```bash
curl -i http://127.0.0.1:8000/api/todos
```

Expected: imported todos appear through the existing `TodoResource` shape.

- [ ] **Step 4: Run import again**

```bash
curl -i -X POST http://127.0.0.1:8000/api/integrations/dummy-json/todos/import \
  -H "Accept: application/json"
```

Expected: no duplicate imported rows for `source = dummyjson`.

---

## Learning Notes

This exercise teaches:

- Third-party HTTP integration with Laravel's `Http` client.
- Mapping external payloads into local domain fields.
- Idempotent imports with `updateOrCreate`.
- Schema evolution with a new migration.
- Transaction usage for batch imports.
- Feature testing third-party APIs with `Http::fake`.
- Keeping HTTP response shape separate from imported raw data.

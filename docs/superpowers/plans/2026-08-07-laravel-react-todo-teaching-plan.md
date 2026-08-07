# Laravel React Todo Teaching Plan

## Purpose

Build the project from scratch for interview practice. You run each step yourself. After each checkpoint, ask me to check it before continuing.

## Current Starting Point

Keep:

- `docker-compose.yml`
- `docs/superpowers/specs/2026-08-07-laravel-react-todo-design.md`
- `docs/superpowers/plans/2026-08-07-laravel-react-todo-teaching-plan.md`

Create later:

- `backend/`
- `frontend/`
- `README.md`

## Ports And Credentials

MariaDB uses host port `3306`.

MySQL Workbench:

- Host: `127.0.0.1`
- Port: `3306`
- Username: `todo_user`
- Password: `todo_password`
- Schema: `laravel_todo`

Laravel `.env` database values:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_todo
DB_USERNAME=todo_user
DB_PASSWORD=todo_password
```

## Phase 1: Start MariaDB

Goal: prove Docker MariaDB is running and Workbench can connect.

You run:

```bash
docker compose up -d
docker compose ps
```

Expected:

- Container name: `laravel_todo_mariadb`
- Port mapping: `3306->3306`
- Status: `healthy` or `health: starting`

Then open MySQL Workbench with the connection details above.

Ask me to check:

- `docker compose ps` output
- Whether Workbench can connect

Interview explanation:

- Docker gives a repeatable database environment.
- Laravel talks to MariaDB through the MySQL driver.
- Host port `3306` maps to container port `3306`.

## Phase 2: Create Laravel Backend

Goal: scaffold a clean Laravel API project.

You run:

```bash
composer create-project laravel/laravel backend
cd backend
php artisan --version
```

Expected:

- `backend/artisan` exists.
- Laravel version prints successfully.

Ask me to check:

- `backend/` structure
- Laravel version output

Interview explanation:

- `artisan` is Laravel's CLI.
- `composer.json` defines PHP dependencies.
- Laravel starts with routes, controllers, models, migrations, and config.

## Phase 3: Connect Laravel To MariaDB

Goal: point Laravel at Docker MariaDB instead of SQLite.

Edit `backend/.env`:

```env
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_todo
DB_USERNAME=todo_user
DB_PASSWORD=todo_password
```

You run:

```bash
php artisan migrate
```

Expected:

- Laravel creates its default tables in MariaDB.
- Workbench shows tables such as `users`, `cache`, `jobs`, and `migrations`.

Ask me to check:

- `.env` database section
- migration output
- Workbench table list if needed

Interview explanation:

- `.env` controls environment-specific settings.
- Migrations version database schema in code.
- The `migrations` table records which migrations have run.

## Phase 4: Create Todo Model And Migration

Goal: create the Todo database table using Laravel migration.

You run:

```bash
php artisan make:model Todo -m
```

Edit `app/Models/Todo.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
        ];
    }
}
```

Edit the generated `database/migrations/*_create_todos_table.php`:

```php
$table->id();
$table->string('title');
$table->text('description')->nullable();
$table->boolean('is_completed')->default(false);
$table->timestamps();
```

You run:

```bash
php artisan migrate
```

Expected:

- `todos` table appears in MariaDB.

Ask me to check:

- Todo model
- Todo migration
- migration output

Interview explanation:

- Model represents an Eloquent entity.
- Migration creates the physical database table.
- `$fillable` protects mass assignment.
- casts make JSON output return `true` or `false`, not `0` or `1`.

## Phase 5: Build Todo API

Goal: expose REST endpoints.

You run:

```bash
php artisan make:controller TodoController --api
```

Create or enable API routes depending on Laravel version:

- If `routes/api.php` exists, use it.
- If it does not exist, create it and wire it in `bootstrap/app.php`.

Endpoints:

- `GET /api/todos`
- `POST /api/todos`
- `PATCH /api/todos/{todo}`
- `DELETE /api/todos/{todo}`

Ask me before coding the controller so I can explain route model binding, validation, and JSON responses with you.

Interview explanation:

- Route maps HTTP verbs to controller methods.
- Controller validates input and returns JSON.
- Route model binding turns `{todo}` into a `Todo` model.

## Phase 6: Test API

Goal: verify backend behavior before building UI.

Use one of:

- Laravel feature tests
- Postman
- curl

Minimum checks:

- List todos.
- Create todo.
- Reject empty title.
- Update todo.
- Delete todo.

Ask me to check:

- test file or curl commands
- output from each request

Interview explanation:

- Backend can be verified independently from frontend.
- API contract should be stable before React depends on it.

## Phase 7: Create React Frontend

Goal: scaffold the frontend separately.

From project root, you run:

```bash
npm create vite@latest frontend -- --template react
cd frontend
npm install
npm run dev
```

Expected:

- Vite dev server starts on `http://127.0.0.1:5173`.

Ask me to check:

- `frontend/` structure
- dev server output

Interview explanation:

- React is a separate client application.
- Vite provides fast development server and build tooling.

## Phase 8: Connect React To Laravel

Goal: fetch and mutate todos from React.

React should call:

- `GET http://127.0.0.1:8000/api/todos`
- `POST http://127.0.0.1:8000/api/todos`
- `PATCH http://127.0.0.1:8000/api/todos/{id}`
- `DELETE http://127.0.0.1:8000/api/todos/{id}`

UI features:

- Add todo.
- List todos.
- Toggle complete.
- Edit todo.
- Delete todo.
- Filter all, active, completed.

Ask me to check:

- `App.jsx`
- browser behavior
- console/network errors

Interview explanation:

- Frontend stores UI state.
- Backend remains the source of truth.
- CORS matters because React and Laravel run on different origins.

## Phase 9: Final README And Walkthrough

Goal: document setup and prepare interview talking points.

README should include:

- Services and ports.
- Workbench connection.
- Setup commands.
- API endpoints.
- Interview walkthrough.

Ask me to check:

- README clarity
- whether commands work from a fresh start

Final interview flow:

1. Docker starts MariaDB.
2. Laravel connects to MariaDB through `.env`.
3. Migrations create tables.
4. Eloquent model maps database rows to PHP objects.
5. Routes and controller expose JSON API.
6. React calls the API and renders UI.

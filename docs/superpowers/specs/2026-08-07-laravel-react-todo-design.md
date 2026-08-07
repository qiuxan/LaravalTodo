# Laravel React Todo Design

## Goal

Build a frontend/backend separated Todo List as an interview learning project. The backend is a Laravel REST API, the frontend is a React + Vite single-page app, and MariaDB runs in Docker so the project does not depend on a locally installed database.

The workflow is teaching-first: the learner runs each step, then Codex checks the files, terminal output, and behavior before moving to the next step.

## Architecture

The repository is split into three top-level parts:

- `backend/`: Laravel API application.
- `frontend/`: React + Vite application.
- `docker-compose.yml`: MariaDB service for local development.

Laravel exposes JSON endpoints under `/api/todos`. React reads and mutates todos through those endpoints. MariaDB stores todo records and can be inspected from MySQL Workbench through `127.0.0.1:3306`.

## Database

Use Docker Compose to run MariaDB.

- Image: `mariadb:11`
- Host port: `3306`
- Database: `laravel_todo`
- User: `todo_user`
- Password: `todo_password`
- Root password: `root_password`

Laravel connects with the MySQL driver:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_todo
DB_USERNAME=todo_user
DB_PASSWORD=todo_password
```

The `todos` table contains:

- `id`: primary key
- `title`: required string
- `description`: nullable text
- `is_completed`: boolean default `false`
- `created_at`: timestamp
- `updated_at`: timestamp

## API

All responses are JSON.

- `GET /api/todos`: returns todos ordered by newest first.
- `POST /api/todos`: creates a todo with `title`, optional `description`, and optional `is_completed`.
- `PATCH /api/todos/{todo}`: updates `title`, `description`, or `is_completed`.
- `DELETE /api/todos/{todo}`: deletes a todo and returns an empty success response.

Validation rules:

- `title` is required for create, optional for update, string, max length 255.
- `description` is optional and nullable.
- `is_completed` is optional and boolean.

Errors:

- Validation errors return Laravel's default `422` JSON response.
- Missing todos return Laravel's default `404` JSON response.

## Frontend

React provides the first screen directly as the Todo app, not a landing page.

Expected controls:

- Add todo form with title and optional description.
- List todos.
- Toggle complete/incomplete.
- Edit title and description inline or through a compact edit state.
- Delete todo.
- Filter by all, active, and completed.
- Show loading and request error states.

Frontend state stays in React. The backend remains the source of truth after each successful API mutation.

## CORS And Local Ports

Local development uses:

- Laravel API: `http://127.0.0.1:8000`
- React app: `http://127.0.0.1:5173`
- MariaDB: `127.0.0.1:3306`

Laravel CORS allows the Vite origin during local development.

## Testing And Verification

Backend verification:

- Run Laravel migrations against Docker MariaDB.
- Add feature tests for list, create, update, delete, and validation.
- Run `php artisan test`.

Frontend verification:

- Run `npm run build`.
- Manually verify the app against the Laravel API.

End-to-end smoke check:

- Start MariaDB.
- Run migrations.
- Start Laravel API.
- Start React app.
- Create, edit, complete, filter, and delete a todo from the browser.

## Scope

Included:

- Dockerized MariaDB.
- Laravel Todo API.
- React + Vite Todo UI.
- README with setup and interview walkthrough commands.
- Step-by-step checkpoints for interview explanation.

Excluded:

- Authentication.
- User accounts.
- Deployment.
- Pagination.
- Full Dockerization of PHP and Node.

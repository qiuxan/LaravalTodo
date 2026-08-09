<?php

namespace App\Actions\Todos;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use App\Models\Todo;
use Illuminate\Support\Facades\DB;

class ImportDummyJsonTodosAction
{
    public function handle(): array
    {

        $response = Http::timeout(10)->get('https://dummyjson.com/todos');

        if ($response->failed()) {
            throw new RuntimeException('Failed to fetch todos from DummyJSON.');
        }

        $remoteTodos = $response->json('todos', []);

        $now = now();

        $rows = collect($remoteTodos)
            ->map(fn(array $remoteTodo): array => [
                ...$this->mapRemoteTodo($remoteTodo),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {

            $placeholders = implode(', ', array_fill(0, count($rows), '(?, ?, ?, ?, ?, ?, ?)'));

            $bindings = [];
            foreach ($rows as $row) {
                $bindings[] = $row[Todo::FIELD_SOURCE];
                $bindings[] = $row[Todo::FIELD_EXTERNAL_ID];
                $bindings[] = $row[Todo::FIELD_TITLE];
                $bindings[] = $row[Todo::FIELD_DESCRIPTION];
                $bindings[] = $row[Todo::FIELD_IS_COMPLETED];
                $bindings[] = $row['created_at'];
                $bindings[] = $row['updated_at'];
            }

            $sql =
                "
                INSERT INTO todos (
                    source,
                    external_id,
                    title,
                    description,
                    is_completed,
                    created_at,
                    updated_at
                )
                VALUES {$placeholders}
                ON DUPLICATE KEY UPDATE
                    title = VALUES(title),
                    description = VALUES(description),
                    is_completed = VALUES(is_completed),
                    updated_at = VALUES(updated_at)
            ";
            DB::statement($sql, $bindings);
        }

        return [
            'status' => $response->status(),
            'imported' => count($rows),
            'source' => 'dummyjson',
        ];
    }

    private function assertValidRemoteTodo(array $remoteTodo): void
    {
        foreach (['id', 'todo', 'completed'] as $key) {
            if (! array_key_exists($key, $remoteTodo)) {
                throw new RuntimeException("DummyJSON todo is missing required field: {$key}");
            }
        }

        if (! is_int($remoteTodo['id'])) {
            throw new RuntimeException('DummyJSON todo id must be an integer.');
        }

        if (! is_string($remoteTodo['todo'])) {
            throw new RuntimeException('DummyJSON todo text must be a string.');
        }

        if (! is_bool($remoteTodo['completed'])) {
            throw new RuntimeException('DummyJSON todo completed must be a boolean.');
        }
    }

    private function mapRemoteTodo(array $remoteTodo): array
    {
        $this->assertValidRemoteTodo($remoteTodo);
        return [
            Todo::FIELD_SOURCE => 'dummyjson',
            Todo::FIELD_EXTERNAL_ID => $remoteTodo['id'],
            Todo::FIELD_TITLE => $remoteTodo['todo'],
            Todo::FIELD_DESCRIPTION => '', // DummyJSON todos don't have a description field
            Todo::FIELD_IS_COMPLETED => $remoteTodo['completed'],
        ];
    }
}

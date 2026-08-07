<?php

namespace App\Actions\Todos;

use App\Models\Todo;

class UpdateTodoAction
{
    public function handle(Todo $todo, array $data): Todo
    {
        $todo->update($data);

        return $todo->fresh();
    }
}

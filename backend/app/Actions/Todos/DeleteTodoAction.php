<?php

namespace App\Actions\Todos;

use App\Models\Todo;

class DeleteTodoAction
{
    public function handle(Todo $todo): void
    {
        $todo->delete();
    }
}

<?php

namespace App\Actions\Todos;

use App\Models\Todo;

class CreateTodoAction
{
    public function handle(array $data): Todo
    {
        return Todo::create($data)->fresh();
    }
}
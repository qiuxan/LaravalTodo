<?php

namespace App\Actions\Todos;

use App\Models\User;
use App\Models\Todo;

class CreateTodoAction
{
    public function handle(User $user, array $data): Todo
    {
        return $user->todos()->create($data);
    }
}

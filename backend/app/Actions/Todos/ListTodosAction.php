<?php

namespace App\Actions\Todos;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListTodosAction
{
    public function handle(User $user): Collection
    {
        return $user->todos()
            ->latest()
            ->get();
    }
}

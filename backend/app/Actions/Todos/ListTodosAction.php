<?php

namespace App\Actions\Todos;

use App\Models\Todo;
use Illuminate\Database\Eloquent\Collection;

class ListTodosAction
{
    public function handle(): Collection
    {
        return Todo::query()->latest()->get();
    }
}

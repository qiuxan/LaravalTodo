<?php

namespace App\Actions\Todos;

class ImportDummyJsonTodosAction
{
    public function handle(): array
    {
        return [
            'message' => 'DummyJSON import action is connected.',
        ];
    }
}

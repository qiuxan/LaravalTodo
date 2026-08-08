<?php

namespace App\Http\Controllers;

use App\Actions\Todos\ImportDummyJsonTodosAction;
use Illuminate\Http\JsonResponse;

class DummyJsonTodoImportController extends Controller
{
    
    public function __invoke(ImportDummyJsonTodosAction $action): JsonResponse
    {
        return response()->json([
            'data' => $action->handle(),
        ]);
    }
}

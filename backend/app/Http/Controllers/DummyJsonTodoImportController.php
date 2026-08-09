<?php

namespace App\Http\Controllers;

use App\Actions\Todos\ImportDummyJsonTodosAction;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class DummyJsonTodoImportController extends Controller
{
    public function __invoke(ImportDummyJsonTodosAction $action): JsonResponse
    {
        try {
            return response()->json([
                'data' => $action->handle(),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\TodoImport;
use Illuminate\Http\JsonResponse;

class DummyJsonTodoImportStatusController extends Controller
{
    public function __invoke(TodoImport $todoImport): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $todoImport->id,
                'source' => $todoImport->source,
                'status' => $todoImport->status,
                'imported_count' => $todoImport->imported_count,
                'error_message' => $todoImport->error_message,
                'started_at' => $todoImport->started_at,
                'finished_at' => $todoImport->finished_at,
            ],
        ]);
    }
}

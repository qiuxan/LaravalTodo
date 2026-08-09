<?php

namespace App\Http\Controllers;

use App\Actions\Todos\ImportDummyJsonTodosAction;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use App\Models\TodoImport;
use App\Jobs\ImportDummyJsonTodosJob;


class DummyJsonTodoImportController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $import = TodoImport::create([
            'source' => 'dummyjson',
            'status' => TodoImport::STATUS_PENDING,
            'imported_count' => 0,
        ]);

        ImportDummyJsonTodosJob::dispatch($import);

        return response()->json([
            'data' => [
                'id' => $import->id,
                'source' => $import->source,
                'status' => $import->status,
            ],
        ], 202);
    }
}

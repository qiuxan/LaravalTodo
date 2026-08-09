<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\TodoImport;
use App\Actions\Todos\ImportDummyJsonTodosAction;
use Throwable;

class ImportDummyJsonTodosJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public TodoImport $todoImport)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ImportDummyJsonTodosAction $action): void
    {
        $this->todoImport->update([
            'status' => TodoImport::STATUS_RUNNING,
            'started_at' => now(),
        ]);
        try {
            $result = $action->handle();

            $this->todoImport->update([
                'status' => TodoImport::STATUS_COMPLETED,
                'imported_count' => $result['imported'],
                'error_message' => null,
                'finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $this->todoImport->update([
                'status' => TodoImport::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            throw $exception;
        }
    }
}

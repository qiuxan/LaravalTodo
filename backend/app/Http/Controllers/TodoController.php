<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Models\Todo;
use App\Http\Requests\StoreTodoRequest;
use App\Http\Requests\UpdateTodoRequest;
use App\Http\Resources\TodoResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Actions\Todos\{
    CreateTodoAction,
    DeleteTodoAction,
    ListTodosAction,
    UpdateTodoAction,
};
use Illuminate\Http\Request;

class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ListTodosAction $action): AnonymousResourceCollection
    {
        return TodoResource::collection($action->handle($request->user()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTodoRequest $request, CreateTodoAction $action): JsonResponse
    {

        $todo = $action->handle($request->user(), $request->validated());

        return (new TodoResource($todo))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Todo $todo): TodoResource
    {
        $this->abortIfTodoDoesNotBelongToUser($todo, $request->user()->id);

        return new TodoResource($todo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTodoRequest $request, Todo $todo, UpdateTodoAction $action): TodoResource
    {
        $this->abortIfTodoDoesNotBelongToUser($todo, $request->user()->id);
        
        $todo = $action->handle($todo, $request->validated());
        return new TodoResource($todo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Todo $todo, DeleteTodoAction $action): JsonResponse
    {
        $this->abortIfTodoDoesNotBelongToUser($todo, $request->user()->id);

        $action->handle($todo);
        return response()->json(null, 204);
    }

    private function abortIfTodoDoesNotBelongToUser(Todo $todo, int $userId): void
    {
        if ($todo->user_id !== $userId) {
            abort(404);
        }
    }
}

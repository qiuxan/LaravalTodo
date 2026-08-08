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

class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ListTodosAction $action): AnonymousResourceCollection
    {
        return TodoResource::collection($action->handle());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTodoRequest $request, CreateTodoAction $action): JsonResponse
    {

        $todo = $action->handle($request->validated());

        return (new TodoResource($todo))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Todo $todo): TodoResource
    {
        return new TodoResource($todo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTodoRequest $request, Todo $todo, UpdateTodoAction $action): TodoResource
    {

        $todo = $action->handle($todo, $request->validated());
        return new TodoResource($todo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Todo $todo, DeleteTodoAction $action): JsonResponse
    {
        $action->handle($todo);
        return response()->json(null, 204);
    }
}

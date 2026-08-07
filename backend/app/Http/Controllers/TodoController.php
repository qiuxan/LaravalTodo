<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Todo;
// use Illuminate\Support\Facades\DB;

class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Todo::query()->latest()->get()
        );

        // $todos = DB::select('SELECT * FROM todos ORDER BY created_at DESC');

        // return response()->json($todos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_completed' => 'boolean',
        ]);

        // DB::insert('INSERT INTO todos (title, description, completed, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())', [
        //     $data['title'],
        //     $data['description'] ?? null,
        //     $data['completed'] ?? false,
        // ]);

        // $todo = DB::select('SELECT * FROM todos WHERE id = LAST_INSERT_ID()');
        //return response()->json($todo, 201);

        $todo = Todo::create($data);

        return response()->json($todo, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Todo $todo): JsonResponse
    {
        return response()->json($todo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Todo $todo): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_completed' => 'sometimes|boolean',
        ]);

        $todo->update($data);

        return response()->json($todo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Todo $todo): JsonResponse
    {
        $todo->delete();

        return response()->json(null, 204);
    }
}

<?php

use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DummyJsonTodoImportController;

Route::apiResource('todos', TodoController::class)->only([
    'index',
    'store',
    'show',
    'update',
    'destroy',
]);

Route::post(
    'integrations/dummy-json/todos/import',
    DummyJsonTodoImportController::class
);
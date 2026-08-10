<?php

use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DummyJsonTodoImportController;
use App\Http\Controllers\DummyJsonTodoImportStatusController;
use App\Http\Controllers\AuthController;

Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

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

Route::get(
    'integrations/dummy-json/todos/imports/{todoImport}',
    DummyJsonTodoImportStatusController::class
);

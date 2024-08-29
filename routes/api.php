<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('test',function(){
    return 'hi Developer. this tesing api.';
});
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});
Route::middleware('auth:api')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('/getAllUsers', [AuthController::class, 'getAllUsers']);
        Route::get('/user/{id}', [AuthController::class, 'getUserById']);
        Route::put('/update', [AuthController::class, 'update']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
    Route::get('/messages/history/{userId}', [ChatController::class, 'messageHistory']);
    Route::get('/messages', [ChatController::class, 'messages'])->name('api.messages');
    Route::post('/messages', [ChatController::class, 'messageStore']);
});
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

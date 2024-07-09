<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WarehouseController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/ingredients/check', [WarehouseController::class, 'checkIngredients']);
Route::post('/ingredients/decrement', [WarehouseController::class, 'decrementIngredients']);
Route::post('/ingredients/replenish', [WarehouseController::class, 'replenishIngredients']);
Route::get('/ingredients/available', [WarehouseController::class, 'getAvailableIngredients']);
Route::get('/ingredients/purchase-history', [WarehouseController::class, 'getPurchaseHistory']);
Route::post('/ingredients/run-job', [WarehouseController::class, 'runJob']);
use App\Services\RabbitMQService;

Route::get('/test-rabbitmq', function () {
    try {
        $rabbitmq = new RabbitMQService();

        // Envía un mensaje de prueba
        $rabbitmq->sendMessage('test_queue', ['message' => 'Hello RabbitMQ!']);

        return response()->json(['message' => 'Message sent to RabbitMQ']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

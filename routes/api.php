<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReceptionistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(AdminController::class)->prefix('/admin')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('users', 'createUser');
        Route::post('users/{user}/role', 'assignRole');
        Route::delete('users/{user}/role', 'removeRole');
    });
});

Route::controller(ItemController::class)->prefix('/items')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
    });
});

Route::controller(ProductController::class)->prefix('/products')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
        Route::post('{product}/purchase', 'purchase');
    });
});

Route::controller(ReceptionistController::class)->prefix('/receptionist')->group(function () {
    Route::middleware(['auth:sanctum', 'role:receptionist'])->group(function () {
        Route::post('patients', 'registerPatient');
    });
});

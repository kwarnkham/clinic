<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitController;
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


Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::delete('logout', 'logout');
        Route::post('change-password', 'changePassword');
    });
});



Route::controller(ItemController::class)->prefix('/items')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
        Route::put('{item}', 'update');
        Route::get('', 'index');
        Route::get('{item}', 'show');
    });
});

Route::controller(PurchaseController::class)->prefix('/purchases')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('', 'index');
        Route::post('{purchase}/cancel', 'cancel');
    });
});

Route::controller(ProductController::class)->prefix('/products')->group(function () {

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::middleware(['role:cashier'])->group(function () {
            Route::get('search', 'search');
        });
        Route::middleware(['role:admin'])->group(function () {
            Route::post('', 'store');
            Route::get('{product}', 'show');
            Route::get('', 'index');
            Route::post('{product}/purchase', 'purchase');
            Route::put('{product}', 'update');
        });
    });
});

Route::controller(UserController::class)->prefix('/users')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::middleware(['role:admin'])->group(function () {
            Route::post('', 'store');
            Route::put('{user}', 'update');
            Route::post('{user}/role', 'toggleRole');
            Route::get('', 'index');
        });
        Route::get('token', 'checkToken');
    });
});


Route::controller(PatientController::class)->prefix('/patients')->group(function () {
    Route::middleware(['auth:sanctum', 'role:receptionist'])->group(function () {
        Route::post('', 'store');
        Route::get('', 'index');
    });
});

Route::controller(RoleController::class)->prefix('/roles')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('', 'index');
    });
});

Route::controller(VisitController::class)->prefix('/visits')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::middleware(['role:receptionist'])->group(function () {
            Route::post('', 'store');
        });
        Route::post('{visit}/products', 'recordProduct');
        Route::get('', 'index');
        Route::get('{visit}', 'show');
    });
});

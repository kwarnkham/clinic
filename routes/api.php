<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitController;
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


Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
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

Route::controller(UserController::class)->prefix('/users')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
        Route::post('{user}/role', 'assignRole');
        Route::delete('{user}/role', 'removeRole');
    });
});


Route::controller(PatientController::class)->prefix('/patients')->group(function () {
    Route::middleware(['auth:sanctum', 'role:receptionist'])->group(function () {
        Route::post('', 'store');
    });
});

Route::controller(VisitController::class)->prefix('/visits')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::middleware(['role:cashier'])->group(function () {
            Route::post('{visit}/products', 'recordProduct');
        });
        Route::middleware(['role:pharmacist'])->group(function () {
            Route::post('{visit}/confirm', 'confirmProduct');
        });
        Route::middleware(['role:cashier'])->group(function () {
            Route::post('{visit}/complete', 'completeVisit');
        });
        Route::middleware(['role:cashier'])->group(function () {
            Route::post('{visit}/cancel', 'cancelVisit');
        });
        Route::middleware(['role:receptionist'])->group(function () {
            Route::post('', 'store');
        });
        Route::get('', 'index');
    });
});

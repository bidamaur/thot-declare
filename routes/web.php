<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardStatisticsController;
use App\Http\Middleware\RoleMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will be
| assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware([RoleMiddleware::class])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/user', [AuthController::class, 'update']);
    Route::get('/dashboard/statistics', [DashboardStatisticsController::class, 'index']);
    Route::middleware('role:admin')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::get('/auth/users', [AuthController::class, 'users']);
        Route::delete('/auth/users/{user}', [AuthController::class, 'destroy']);
        Route::post('/auth/users/{user}/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/auth/users/{user}/switch', [AuthController::class, 'switchUser']);
    });
});

// SPA fallback for Vue router history mode
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
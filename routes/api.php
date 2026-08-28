<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CdrEncoursController;
use App\Http\Controllers\BkdosprtController;
use App\Http\Controllers\BkcliController;
use App\Http\Controllers\CdrEngagementsController;
use App\Http\Controllers\GarantiesController;
use App\Http\Controllers\CdrPpController;
use App\Http\Controllers\CdrPmController;
use App\Http\Controllers\AdminConfigController;


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
Route::get('/cdr_pp/{DateArr?}/{clientId?}', [CdrPpController::class, 'index']);
Route::get('/cdr_pm/{DateArr?}/{clientId?}', [CdrPmController::class, 'index']);
Route::get('/cdr_engagements/{DateArr}', [CdrEngagementsController::class, 'GetEngagements']);
Route::get('/cdr_ctrEngagements', [CdrEngagementsController::class, 'ctrEngagements']);
Route::get('/cdr_ctrEngagements/compare/{DateArr}/{DateDeb}', [CdrEngagementsController::class, 'compareEngagements']);
Route::get('/cdr_encours/{DateArr}', [CdrEncoursController::class, 'GetEncours']);
Route::get('/cdr_encours_ajust/{DateArr}', [CdrEncoursController::class, 'GetEncoursAjust']);
Route::get('/cdr_garanties/{DateArr}', [GarantiesController::class, 'getGaranties']);
//route mise en application apres 
Route::get('/cdr_encours', [CdrEncoursController::class, 'index']);
Route::get('/cdr_engagements_echus/{DateArr}/{DateDeb}', [CdrEngagementsController::class, 'GetEngagementsEchus']);
Route::get('/admin/config', [AdminConfigController::class, 'show']);
Route::put('/admin/config', [AdminConfigController::class, 'update']);
Route::post('/admin/database/test', [AdminConfigController::class, 'testDatabase']);
Route::get('/admin/queries', [AdminConfigController::class, 'queries']);
Route::put('/admin/queries/{key}', [AdminConfigController::class, 'updateQuery']);
Route::post('/admin/queries/{key}/test', [AdminConfigController::class, 'testQuery']);
Route::get('/admin/themes', [AdminConfigController::class, 'listThemes']);
Route::post('/admin/themes/select', [AdminConfigController::class, 'selectTheme']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


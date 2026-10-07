<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\ServiceAssetController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy']);

    Route::get('/', DashboardController::class);

    Route::get('/residenciales', [CommunityController::class, 'index']);
    Route::get('/residenciales/{community}', [CommunityController::class, 'show']);
    Route::get('/unidades/buscar', [UserController::class, 'searchUnits']);
    Route::get('/unidades/{unit}', [UnitController::class, 'show']);
    Route::get('/servicios', [ServiceAssetController::class, 'index']);
    Route::post('/servicios', [ServiceAssetController::class, 'store']);
    Route::patch('/servicios/mantenimientos/{maintenance}/completar', [ServiceAssetController::class, 'complete']);
    Route::patch('/servicios/{asset}', [ServiceAssetController::class, 'update']);
    Route::post('/servicios/{asset}/mantenimientos', [ServiceAssetController::class, 'schedule']);
    Route::get('/incidencias', [IncidentController::class, 'index']);
    Route::get('/incidencias/crear', [IncidentController::class, 'create']);
    Route::post('/incidencias', [IncidentController::class, 'store']);
    Route::get('/incidencias/{incident:code}', [IncidentController::class, 'show']);
    Route::patch('/incidencias/{incident:code}/estado', [IncidentController::class, 'status']);
    Route::patch('/incidencias/{incident:code}/asignar', [IncidentController::class, 'assign']);
    Route::patch('/incidencias/{incident:code}/detalle', [IncidentController::class, 'detail']);
    Route::post('/incidencias/{incident:code}/evidencias', [IncidentController::class, 'evidence']);
    Route::post('/incidencias/{incident:code}/comentarios', [IncidentController::class, 'comment']);
    Route::post('/incidencias/{incident:code}/confirmacion', [IncidentController::class, 'confirm']);

    Route::get('/cobros', [ChargeController::class, 'index']);
    Route::get('/cobros/exportar', [ChargeController::class, 'export']);
    Route::post('/cobros', [ChargeController::class, 'store']);
    Route::post('/cobros/generar', [ChargeController::class, 'generate']);
    Route::post('/cobros/pagos', [ChargeController::class, 'payUnit']);
    Route::post('/cobros/{charge}/pagos', [ChargeController::class, 'pay']);
    Route::patch('/cobros/{charge}/legal', [ChargeController::class, 'legal']);
    Route::get('/usuarios', [UserController::class, 'index']);
    Route::post('/usuarios', [UserController::class, 'store']);
    Route::patch('/usuarios/{user}', [UserController::class, 'update']);

    Route::get('/avisos', [NoticeController::class, 'index']);
    Route::post('/avisos/{notice}/leido', [NoticeController::class, 'read']);

    Route::get('/seguridad', [VisitorController::class, 'index']);
    Route::post('/seguridad/visitas', [VisitorController::class, 'store']);
    Route::patch('/seguridad/visitas/{visitor}/salida', [VisitorController::class, 'checkout']);
});

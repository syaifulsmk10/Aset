<?php

use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataApplicantController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


Route::post("/login", [UserController::class, 'postLogin'])->name("login");
Route::get('/register', [DashboardController::class, 'register']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'getUser']); 
    Route::get('/dashboard', [DashboardController::class, 'getDashboardData']);
  
    
    Route::prefix('/employee')->group(function () {
    Route::get('/index', [EmployeeController::class, 'index']);
    Route::post('/create', [EmployeeController::class, 'create']);
    Route::post('/update/{id}', [EmployeeController::class, 'update']);
    Route::delete('/delete/{id}', [EmployeeController::class, 'delete']);
    Route::delete('/reset', [EmployeeController::class, 'reset']);
});

    Route::prefix('/applicant')->group(function () {
    Route::get('/index', [ApplicantController::class, 'index']);
    Route::post('/create', [ApplicantController::class, 'create']); //belum success
     Route::delete('/delete/{id}', [ApplicantController::class, 'delete']);
});

    Route::prefix('/data/applicant')->group(function () {
    Route::get('/index', [DataApplicantController::class, 'index']);
    Route::get('/index/{id}', [DataApplicantController::class, 'detail']);
    Route::post('/accepted/{id}', [DataApplicantController::class, 'accept']); 
    Route::post('/denied/{id}', [DataApplicantController::class, 'denied']);
    Route::delete('/reset', [DataApplicantController::class, 'reset']);
});

Route::prefix('/aset')->group(function () {
    Route::get('/index', [AssetController::class, 'index']);
    Route::post('/create', [AssetController::class, 'create']); //belum success
    Route::post('/update/{id}', [AssetController::class, 'update']);
    Route::delete('/delete/{id}', [AssetController::class, 'delete']);
});

    



});

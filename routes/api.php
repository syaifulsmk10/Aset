<?php

use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataApplicantController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PositionController;
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


Route::post("/login", [UserController::class, 'postLogin'])->name("login"); //done
Route::post('/register', [UserController::class, 'registerUser']); 


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/detail', [UserController::class, 'getUser']);  //done
    Route::post('/update', [UserController::class, 'update']); //done
    Route::get('/dashboard', [DashboardController::class, 'getDashboardData']); //done
  
    
    Route::prefix('/employee')->group(function () {
    Route::get('/index', [EmployeeController::class, 'index']); //done
    Route::post('/create', [EmployeeController::class, 'create']);  //done
     Route::get('/detail/{id}', [EmployeeController::class, 'detail']);  //done
    Route::post('/update/{id}', [EmployeeController::class, 'update']);  //done
    Route::delete('/delete/{id}', [EmployeeController::class, 'delete']);  //done
    Route::delete('/reset', [EmployeeController::class, 'reset']);  //done 
});

    Route::prefix('/applicant')->group(function () {
    Route::get('/index', [ApplicantController::class, 'index']); //done
    Route::get('/index/{id}', [ApplicantController::class, 'detail']);  //done
    Route::get('/detail/{id}', [ApplicantController::class, 'detil']); //done
    Route::post('/create', [ApplicantController::class, 'create']); //done
    Route::post('/update/{id}', [ApplicantController::class, 'update']); //done
    Route::delete('/delete/{id}', [ApplicantController::class, 'delete']);  //done
});

    Route::prefix('/data/applicant')->group(function () {
    Route::get('/index', [DataApplicantController::class, 'index']); 
    Route::get('/index/{id}', [DataApplicantController::class, 'detail']); 
    Route::post('/accepted/{id}', [DataApplicantController::class, 'accept']);  
    Route::post('/denied/{id}', [DataApplicantController::class, 'denied']) ; 
    Route::delete('/delete/{id}', [DataApplicantController::class, 'delete']); 
    Route::delete('/reset', [DataApplicantController::class, 'reset']); 
});

Route::prefix('/aset')->group(function () {
    Route::get('/index', [AssetController::class, 'index']); //done
    Route::get('/detail/{id}', [AssetController::class, 'detail']);  //done
    Route::post('/create', [AssetController::class, 'create']); //done
    Route::post('/update/{id}', [AssetController::class, 'update']); //done
    Route::delete('/delete/{id}', [AssetController::class, 'delete']); //done
});  


Route::prefix('/category')->group(function () {
    Route::get('/index', [CategoryController::class, 'index']); 
    Route::post('/create', [CategoryController::class, 'create']);  
    Route::post('/update/{id}', [CategoryController::class, 'update']);  
    Route::delete('/delete/{id}', [CategoryController::class, 'delete']); 
});


Route::prefix('/position')->group(function () {
    Route::get('/index', [PositionController::class, 'index']); 
    Route::post('/create', [PositionController::class, 'create']);  
    Route::post('/update/{id}', [PositionController::class, 'update']);  
    Route::delete('/delete/{id}', [PositionController::class, 'delete']); 
});

Route::prefix('/department')->group(function () {
    Route::get('/index', [DepartmentController::class, 'index']); 
    Route::post('/create', [DepartmentController::class, 'create']);  
    Route::post('/update/{id}', [DepartmentController::class, 'update']);  
    Route::delete('/delete/{id}', [DepartmentController::class, 'delete']); 
});
});

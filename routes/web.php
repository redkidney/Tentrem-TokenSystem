<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TokenController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Registry routes
Route::get('/registry', [TokenController::class, 'showRegistry'])->name('registry')->middleware('auth');
Route::post('/generate-token', [TokenController::class, 'generateToken'])->name('generate-token')->middleware('auth');

//Customer routes
Route::get('/customer/{port}', [TokenController::class, 'showCustomer'])->name('customer');
Route::post('/customer/validate', [TokenController::class, 'validateToken'])->name('customer.validate');
Route::get('/charging-ports', [TokenController::class, 'showBoth'])->name('ports-both');

// Start and end charging
Route::post('/start-charging', [TokenController::class, 'startCharging'])->name('start-charging');
Route::post('/customer/{port}/end', [TokenController::class, 'endCharging'])->name('end-charging');

// New route for fetching port status as JSON
Route::get('/customer/{port}/status', [TokenController::class, 'getPortStatus'])->name('customer.port-status');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');


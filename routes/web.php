<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\ReportController;
use App\Events\ChargingStatus;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Routes are organized into logical groups:
| - Authentication & Home
| - Customer Charging
| - Admin & Protected Routes
| - Reports
|
*/

/*
|--------------------------------------------------------------------------
| Authentication & Home Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Auth::check() 
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Auth::routes();

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Customer Charging Routes
|--------------------------------------------------------------------------
*/
Route::prefix('customer')->group(function () {
    Route::post('/validate', [TokenController::class, 'validateToken'])->name('customer.validate');
    Route::post('/{port}/end', [TokenController::class, 'endCharging'])->name('end-charging');
    Route::post('/{port}/cancel', [TokenController::class, 'cancelCharging'])->name('customer.cancel');
});

Route::get('/charging-ports', [TokenController::class, 'showBoth'])->name('ports-both');
Route::post('/start-charging', [TokenController::class, 'startCharging'])->name('start-charging');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Registry Management
    Route::prefix('registry')->group(function () {
        Route::get('/', [TokenController::class, 'showRegistry'])->name('registry');
        Route::post('/generate-token', [TokenController::class, 'generateToken'])->name('generate-token');
    });

    // Voucher Management
    Route::prefix('vouchers')->group(function () {
        Route::get('/', [VoucherController::class, 'index'])->name('vouchers.create');
        Route::post('/', [VoucherController::class, 'store'])->name('vouchers.store');
        Route::get('/{voucher}/edit', [VoucherController::class, 'edit'])->name('vouchers.edit');
        Route::put('/{voucher}', [VoucherController::class, 'update'])->name('vouchers.update');
        Route::delete('/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
    });

    // Admin Monitoring
    Route::prefix('admin')->group(function () {
        Route::get('/monitor', [TokenController::class, 'showMonitor'])->name('admin.monitor');
        Route::get('/port/{port}/current', [TokenController::class, 'getCurrent']);
        Route::post('/charging/{port}/cancel', [TokenController::class, 'cancelCharging'])->name('admin.charging.cancel');
    });
});

/*
|--------------------------------------------------------------------------
| Report Routes
|--------------------------------------------------------------------------
*/
Route::prefix('reports')->middleware(['auth'])->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('reports.charging-sessions');
    Route::get('/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');
});

/*
|--------------------------------------------------------------------------
| Development/Testing Routes
|--------------------------------------------------------------------------
*/
if (app()->environment('local', 'development')) {
    Route::get('/test-event', function() {
        event(new ChargingStatus('charging_started', 1));
        return 'Event dispatched';
    });
}
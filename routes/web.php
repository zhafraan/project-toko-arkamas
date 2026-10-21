<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\DeletionLogController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:10,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated routes
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', function() { return redirect()->route('dashboard'); });

    // POS / Kasir
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/',           [PosController::class, 'index'])->name('index');
        Route::post('/',          [PosController::class, 'store'])->name('store');
        Route::get('/history',    [PosController::class, 'history'])->name('history');
        Route::delete('/history/{transaction}', [PosController::class, 'destroy'])->name('destroy')->middleware('role:owner');
        Route::get('/receipt/{transaction}', [PosController::class, 'receipt'])->name('receipt');
        Route::post('/customer',  [PosController::class, 'storeCustomer'])->name('storeCustomer');
    });

    // Pelanggan
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/',              [CustomerController::class, 'index'])->name('index');
        Route::post('/',             [CustomerController::class, 'store'])->name('store');
        Route::get('/search',        [CustomerController::class, 'search'])->name('search');
        Route::get('/{customer}',    [CustomerController::class, 'show'])->name('show');
        Route::put('/{customer}',    [CustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy')->middleware('role:owner');
    });

    // Peminjaman Tabung
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/', [LoanController::class, 'index'])->name('index');
        Route::post('/{loan}/return', [LoanController::class, 'returnTube'])->name('return');
    });

    // Piutang
    Route::prefix('debts')->name('debts.')->group(function () {
        Route::get('/', [DebtController::class, 'index'])->name('index');
        Route::post('/{transaction}/pay', [DebtController::class, 'pay'])->name('pay');
    });

    // Stok (kasir boleh melihat) & pengelolaan produk / kiriman agen (pemilik)
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');

        Route::middleware('role:owner')->group(function () {
            Route::post('/',                      [InventoryController::class, 'store'])->name('store');
            Route::put('/{product}',              [InventoryController::class, 'update'])->name('update');
            Route::delete('/{product}',           [InventoryController::class, 'destroy'])->name('destroy');
            Route::post('/{product}/adjust-stock',[InventoryController::class, 'adjustStock'])->name('adjustStock');
        });

        // Kiriman agen: hanya pemilik (menu ini memang disembunyikan dari kasir)
        Route::middleware('role:owner')->group(function () {
            Route::get('/deliveries',  [InventoryController::class, 'deliveries'])->name('deliveries');
            Route::post('/deliveries', [InventoryController::class, 'storeDelivery'])->name('storeDelivery');
            Route::delete('/deliveries/{delivery}', [InventoryController::class, 'destroyDelivery'])->name('destroyDelivery');
        });
    });

    // Laporan (Owner only)
    Route::middleware('role:owner')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/',            [ReportController::class, 'index'])->name('index');
        Route::get('/export-pdf',  [ReportController::class, 'exportPdf'])->name('exportPdf');
        Route::get('/export-excel',[ReportController::class, 'exportExcel'])->name('exportExcel');
    });

    // Manajemen Pengguna (Owner only)
    Route::middleware('role:owner')->prefix('users')->name('users.')->group(function () {
        Route::get('/',         [UserController::class, 'index'])->name('index');
        Route::post('/',        [UserController::class, 'store'])->name('store');
        Route::put('/{user}',   [UserController::class, 'update'])->name('update');
        Route::delete('/{user}',[UserController::class, 'destroy'])->name('destroy');
    });

    // Riwayat audit data (Owner only) — penghapusan & penyesuaian stok
    Route::middleware('role:owner')->prefix('deletion-logs')->name('deletion-logs.')->group(function () {
        Route::get('/',            [DeletionLogController::class, 'index'])->name('index');
        Route::get('/penyesuaian', [DeletionLogController::class, 'adjustments'])->name('adjustments');
    });
});

<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Web\ReportExportController;
use App\Livewire\Anomalies;
use App\Livewire\DeviceDetail;
use App\Livewire\Devices;
use App\Livewire\Events;
use App\Livewire\Home;
use App\Livewire\PosSimulator;
use App\Livewire\Products;
use App\Livewire\Profile;
use App\Livewire\Reconciliation;
use App\Livewire\Reports;
use App\Livewire\Sensors;
use App\Livewire\Settings;
use App\Livewire\Users;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', Home::class)->name('dashboard');
    Route::get('/dashboard/profil', Profile::class)->name('dashboard.profile');
    Route::get('/dashboard/perangkat', Devices::class)
        ->middleware('permission:shelf.devices.view')
        ->name('dashboard.devices');
    Route::get('/dashboard/perangkat/{device}', DeviceDetail::class)
        ->middleware('permission:shelf.devices.view')
        ->name('dashboard.devices.show');
    Route::get('/dashboard/produk', Products::class)
        ->middleware('permission:shelf.devices.view')
        ->name('dashboard.products');
    Route::get('/dashboard/sensor', Sensors::class)
        ->middleware('permission:shelf.sensors.view')
        ->name('dashboard.sensors');
    Route::get('/dashboard/riwayat', Events::class)
        ->middleware('permission:shelf.events.view')
        ->name('dashboard.events');
    Route::get('/dashboard/anomali', Anomalies::class)
        ->middleware('permission:shelf.anomalies.view')
        ->name('dashboard.anomalies');
    Route::get('/dashboard/kasir', PosSimulator::class)
        ->middleware('permission:shelf.pos.manage')
        ->name('dashboard.pos');
    Route::get('/dashboard/rekonsiliasi', Reconciliation::class)
        ->middleware('permission:shelf.reconciliation.view')
        ->name('dashboard.reconciliation');
    Route::get('/dashboard/laporan', Reports::class)
        ->middleware('permission:shelf.reports.view')
        ->name('dashboard.reports');
    Route::get('/dashboard/laporan.csv', [ReportExportController::class, 'csv'])
        ->middleware('permission:shelf.reports.view')
        ->name('dashboard.reports.csv');
    Route::get('/dashboard/laporan/cetak', [ReportExportController::class, 'print'])
        ->middleware('permission:shelf.reports.view')
        ->name('dashboard.reports.print');
    Route::get('/dashboard/pengaturan', Settings::class)
        ->middleware('permission:shelf.settings.manage')
        ->name('dashboard.settings');
    Route::get('/dashboard/pengguna', Users::class)
        ->middleware('permission:users.view')
        ->name('dashboard.users');
});

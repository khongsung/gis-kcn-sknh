<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MapController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PlotController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\FactoryController;
use App\Http\Controllers\Admin\RainwaterManholeController;
use App\Http\Controllers\Admin\WastewaterManholeController;
use App\Http\Controllers\Admin\RoadController;
use App\Http\Controllers\Admin\GreeneryController;
use App\Http\Controllers\Admin\WaterBodyController;
use App\Http\Controllers\Admin\IndustrialZoneBoundaryController;
use App\Http\Controllers\Admin\LightingCabinetController;
use App\Http\Controllers\Admin\LightingCableController;
use App\Http\Controllers\Admin\LightingPoleController;
use App\Http\Controllers\Admin\SurroundingLocationController;
use App\Http\Controllers\Admin\IndustrialZoneController;
use App\Http\Controllers\Admin\WaterPipeController;
use App\Http\Controllers\Admin\CompanyLandController;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [MapController::class, 'index'])->name('map.index');

// Route Đăng nhập / Đăng xuất
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::any('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [ContactController::class, 'sendMail'])->name('contact.send');

// KHU VỰC BẢO VỆ DÀNH RIÊNG CHO ADMIN (Cần đăng nhập & Role Admin)
Route::middleware(['auth', \App\Http\Middleware\CheckAdminRole::class])->prefix('admin')->group(function () {
    
    // Route Dashboard Admin
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['auth', 'verified'])
        ->name('admin.dashboard');


    // QUẢN LÝ USER
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
    Route::post('/users/{id}/toggle-block', [UserController::class, 'toggleBlock'])->name('admin.users.toggleBlock');

    // QUẢN LÝ LÔ ĐẤT
    Route::get('/plots', [PlotController::class, 'index'])->name('admin.plots.index');
    Route::post('/plots', [PlotController::class, 'store'])->name('admin.plots.store');
    Route::put('/plots/{id}', [PlotController::class, 'update'])->name('admin.plots.update');
    Route::delete('/plots/{id}', [PlotController::class, 'destroy'])->name('admin.plots.destroy');

    // QUẢN LÝ CÔNG TY
    Route::get('/companies', [CompanyController::class, 'index'])->name('admin.companies.index');
    Route::post('/companies', [CompanyController::class, 'store'])->name('admin.companies.store');
    Route::put('/companies/{id}', [CompanyController::class, 'update'])->name('admin.companies.update');
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy'])->name('admin.companies.destroy');

    // QUẢN LÝ NHÀ XƯỞNG
    Route::get('/factories', [FactoryController::class, 'index'])->name('admin.factories.index');
    Route::post('/factories', [FactoryController::class, 'store'])->name('admin.factories.store');
    Route::put('/factories/{id}', [FactoryController::class, 'update'])->name('admin.factories.update');
    Route::delete('/factories/{id}', [FactoryController::class, 'destroy'])->name('admin.factories.destroy');

    // QUẢN LÝ HỐ GA NƯỚC MƯA
    Route::get('/rainwater-manholes', [RainwaterManholeController::class, 'index'])->name('admin.rainwater.index');
    Route::post('/rainwater-manholes', [RainwaterManholeController::class, 'store'])->name('admin.rainwater.store');
    Route::put('/rainwater-manholes/{id}', [RainwaterManholeController::class, 'update'])->name('admin.rainwater.update');
    Route::delete('/rainwater-manholes/{id}', [RainwaterManholeController::class, 'destroy'])->name('admin.rainwater.destroy');

    // QUẢN LÝ HỐ GA NƯỚC THẢI
    Route::get('/wastewater-manholes', [WastewaterManholeController::class, 'index'])->name('admin.wastewater.index');
    Route::post('/wastewater-manholes', [WastewaterManholeController::class, 'store'])->name('admin.wastewater.store');
    Route::put('/wastewater-manholes/{id}', [WastewaterManholeController::class, 'update'])->name('admin.wastewater.update');
    Route::delete('/wastewater-manholes/{id}', [WastewaterManholeController::class, 'destroy'])->name('admin.wastewater.destroy');

    // QUẢN LÝ ĐƯỜNG GIAO THÔNG
    Route::get('/roads', [RoadController::class, 'index'])->name('admin.roads.index');
    Route::post('/roads', [RoadController::class, 'store'])->name('admin.roads.store');
    Route::put('/roads/{id}', [RoadController::class, 'update'])->name('admin.roads.update');
    Route::delete('/roads/{id}', [RoadController::class, 'destroy'])->name('admin.roads.destroy');

    // Quản lý Cây Xanh
    Route::get('/greenery', [GreeneryController::class, 'index'])->name('admin.greenery.index');
    Route::post('/greenery', [GreeneryController::class, 'store'])->name('admin.greenery.store');
    Route::put('/greenery/{id}', [GreeneryController::class, 'update'])->name('admin.greenery.update');
    Route::delete('/greenery/{id}', [GreeneryController::class, 'destroy'])->name('admin.greenery.destroy');

    // Quản lý Mặt Nước
    Route::get('/water-bodies', [WaterBodyController::class, 'index'])->name('admin.water-bodies.index');
    Route::post('/water-bodies', [WaterBodyController::class, 'store'])->name('admin.water-bodies.store');
    Route::put('/water-bodies/{id}', [WaterBodyController::class, 'update'])->name('admin.water-bodies.update');
    Route::delete('/water-bodies/{id}', [WaterBodyController::class, 'destroy'])->name('admin.water-bodies.destroy');

    // Quản lý ranh giới quy hoạch KCN
    Route::resource('boundaries', IndustrialZoneBoundaryController::class)->except(['create', 'show', 'edit']);


    // Quản lý Tủ điện chiếu sáng
    Route::resource('lighting-cabinets', LightingCabinetController::class)->except(['create', 'show', 'edit']);

    // Quản lý cáp chiếu sáng 
    Route::resource('lighting-cables', LightingCableController::class)->except(['create', 'show', 'edit']);

    // Quản lý cột đèn
    Route::resource('lighting-poles', LightingPoleController::class)->except(['create', 'show', 'edit']);

    // Quản lý các vùng lân cận
    Route::resource('surrounding-locations', SurroundingLocationController::class)->except(['create', 'show', 'edit']);

    // Quản lý khu công nghiệp
    Route::resource('industrial-zones', IndustrialZoneController::class)->except(['create', 'show', 'edit']);

    //  Quản lý đường ống nước mưa, nước thải
    Route::resource('water-pipes', WaterPipeController::class)->only([
        'index', 'store', 'update', 'destroy'
    ]);

    // Quản lý đất các công ty đã thuê
    Route::resource('company-lands', CompanyLandController::class)->except(['create', 'show', 'edit']);

});


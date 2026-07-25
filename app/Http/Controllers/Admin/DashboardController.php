<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Factory;
use App\Models\Greenery;
use App\Models\Plot;
use App\Models\RainwaterManhole;
use App\Models\Road;
use App\Models\WaterBody;
use App\Models\WastewaterManhole;
use App\Models\RecruitmentNews; // Nếu đã tạo Model Tuyển dụng
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Hiển thị trang Dashboard Tổng quan KCN
     */
    public function index(): Response
    {
        $user = Auth::user();

        // 🌟 Thống kê toàn bộ chỉ số hạ tầng & hoạt động KCN bằng Eloquent Models
        $stats = [
            'companies_count'        => Company::count(),
            'factories_count'        => Factory::count(),
            'rented_factories_count' => Factory::where('status', 'rented')->count(),
            'plots_count'            => Plot::count(),
            
            // Hạ tầng GIS
            'roads_count'            => class_exists(Road::class) ? Road::count() : DB::table('roads')->count(),
            'greenery_count'         => Greenery::count(),
            'water_bodies_count'     => WaterBody::count(),

            // Điểm kỹ thuật & Tin tuyển dụng
            'wastewater_count'       => WastewaterManhole::count(),
            'rainwater_count'        => RainwaterManhole::count(),
            'jobs_count'             => class_exists(RecruitmentNews::class) 
                                        ? RecruitmentNews::count() 
                                        : DB::table('recruitment_news')->count(),
        ];

        // 🌟 Lấy 5 công ty mới đăng ký gần đây nhất qua Eloquent Model Company
        $latestCompanies = Company::latest('created_at')
            ->take(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'company',
                ]
            ],
            'stats'           => $stats,
            'latestCompanies' => $latestCompanies
        ]);
    }
}
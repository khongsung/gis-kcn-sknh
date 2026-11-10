<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyLand;
use App\Models\IndustrialZone;
use App\Models\Plot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CompanyLandController extends Controller
{
    /**
     * 1. Danh sách Đất doanh nghiệp kèm bộ lọc và dữ liệu liên kết
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = CompanyLand::query()
            ->with([
                'company:id,name,tax_code,color,phone,email',
                'plot:id,code,total_area,land_type',
                'zone:id,code,name'
            ])
            ->select(
                'company_lands.*',
                DB::raw('ST_AsGeoJSON(company_lands.geom) as geojson')
            );

        // Lọc theo từ khóa tìm kiếm (Mã đất, Số bìa đỏ, Tên công ty)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('land_code', 'ilike', "%{$search}%")
                  ->orWhere('certificate_number', 'ilike', "%{$search}%")
                  ->orWhere('contract_number', 'ilike', "%{$search}%")
                  ->orWhereHas('company', function ($cq) use ($search) {
                      $cq->where('name', 'ilike', "%{$search}%")
                         ->orWhere('tax_code', 'ilike', "%{$search}%");
                  });
            });
        }

        // Lọc theo Khu công nghiệp
        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        // Lọc theo Hiện trạng sử dụng
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $companyLands = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        // Danh sách phụ trợ cho Dropdown Modal
        $companies = Company::select('id', 'name', 'tax_code', 'color')
            ->orderBy('name', 'asc')
            ->get();

        $plots = Plot::select('id', 'code', 'total_area', 'land_type')
            ->orderBy('code', 'asc')
            ->get();

        $zones = class_exists(IndustrialZone::class) 
            ? IndustrialZone::select('id', 'code', 'name')->where('is_active', true)->get()
            : DB::table('industrial_zones')->select('id', 'code', 'name')->get();

        return Inertia::render('Admin/CompanyLands/Index', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'company',
                ]
            ],
            'companyLands' => $companyLands,
            'companies'    => $companies,
            'plots'        => $plots,
            'zones'        => $zones,
            'filters'      => $request->only(['search', 'zone_id', 'status'])
        ]);
    }

    /**
     * 2. Thêm mới thửa đất doanh nghiệp & tự động tính diện tích GIS
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'land_code'              => 'required|string|max:50|unique:company_lands,land_code',
            'company_id'             => 'required|exists:companies,id',
            'plot_id'                => 'nullable|exists:plots,id',
            'zone_id'                => 'nullable|exists:industrial_zones,id',
            'certificate_number'     => 'nullable|string|max:100',
            'cadastral_book_number'  => 'nullable|string|max:100',
            'issue_date'             => 'nullable|date',
            'issuing_authority'      => 'nullable|string|max:255',
            'contract_number'        => 'nullable|string|max:100',
            'contract_date'          => 'nullable|date',
            'lease_start_date'       => 'nullable|date',
            'lease_end_date'         => 'nullable|date|after_or_equal:lease_start_date',
            'payment_type'           => 'required|in:one_time,annual',
            'leased_area'            => 'nullable|numeric|min:0',
            'land_purpose'           => 'nullable|string|max:255',
            'building_density_limit' => 'nullable|numeric|min:0|max:100',
            'status'                 => 'required|in:active,under_construction,vacant,disputed',
            'is_handed_over'         => 'boolean',
            'description'            => 'nullable|string',
            'geojson'                => 'nullable|string',
        ]);

        $geomJson = !empty($validated['geojson']) ? $validated['geojson'] : null;
        $inputArea = (isset($validated['leased_area']) && $validated['leased_area'] !== '' && $validated['leased_area'] > 0)
            ? $validated['leased_area']
            : null;

        // Lưu thông tin bản ghi ban đầu
        $companyLand = CompanyLand::create([
            'land_code'              => $validated['land_code'],
            'company_id'             => $validated['company_id'],
            'plot_id'                => $validated['plot_id'] ?? null,
            'zone_id'                => $validated['zone_id'] ?? null,
            'certificate_number'     => $validated['certificate_number'] ?? null,
            'cadastral_book_number'  => $validated['cadastral_book_number'] ?? null,
            'issue_date'             => $validated['issue_date'] ?? null,
            'issuing_authority'      => $validated['issuing_authority'] ?? 'UBND tỉnh Bắc Giang',
            'contract_number'        => $validated['contract_number'] ?? null,
            'contract_date'          => $validated['contract_date'] ?? null,
            'lease_start_date'       => $validated['lease_start_date'] ?? null,
            'lease_end_date'         => $validated['lease_end_date'] ?? null,
            'payment_type'           => $validated['payment_type'],
            'leased_area'            => $inputArea,
            'calculated_area'        => null,
            'land_purpose'           => $validated['land_purpose'] ?? null,
            'building_density_limit' => $validated['building_density_limit'] ?? null,
            'status'                 => $validated['status'],
            'is_handed_over'         => $validated['is_handed_over'] ?? true,
            'description'            => $validated['description'] ?? null,
            'geom'                   => null,
        ]);

        // Cập nhật không gian PostGIS và tự tính toán diện tích
        if ($geomJson) {
            if (empty($inputArea)) {
                // Tự tính cả diện tích đo đạc và diện tích pháp lý nếu người dùng bỏ trống
                DB::statement("
                    UPDATE company_lands 
                    SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326),
                        calculated_area = ROUND(ST_Area(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography)::numeric, 2),
                        leased_area = ROUND(ST_Area(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography)::numeric, 2)
                    WHERE id = ?
                ", [$geomJson, $geomJson, $geomJson, $companyLand->id]);
            } else {
                DB::statement("
                    UPDATE company_lands 
                    SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326),
                        calculated_area = ROUND(ST_Area(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography)::numeric, 2)
                    WHERE id = ?
                ", [$geomJson, $geomJson, $companyLand->id]);
            }
        }

        return redirect()->back()->with('success', "Thêm mới thửa đất [{$companyLand->land_code}] thành công!");
    }

    /**
     * 3. Cập nhật thông tin thửa đất doanh nghiệp
     */
    public function update(Request $request, $id)
    {
        $companyLand = CompanyLand::findOrFail($id);

        $validated = $request->validate([
            'land_code'              => 'required|string|max:50|unique:company_lands,land_code,' . $companyLand->id,
            'company_id'             => 'required|exists:companies,id',
            'plot_id'                => 'nullable|exists:plots,id',
            'zone_id'                => 'nullable|exists:industrial_zones,id',
            'certificate_number'     => 'nullable|string|max:100',
            'cadastral_book_number'  => 'nullable|string|max:100',
            'issue_date'             => 'nullable|date',
            'issuing_authority'      => 'nullable|string|max:255',
            'contract_number'        => 'nullable|string|max:100',
            'contract_date'          => 'nullable|date',
            'lease_start_date'       => 'nullable|date',
            'lease_end_date'         => 'nullable|date|after_or_equal:lease_start_date',
            'payment_type'           => 'required|in:one_time,annual',
            'leased_area'            => 'nullable|numeric|min:0',
            'land_purpose'           => 'nullable|string|max:255',
            'building_density_limit' => 'nullable|numeric|min:0|max:100',
            'status'                 => 'required|in:active,under_construction,vacant,disputed',
            'is_handed_over'         => 'boolean',
            'description'            => 'nullable|string',
            'geojson'                => 'nullable|string',
        ]);

        $geomJson = !empty($validated['geojson']) ? $validated['geojson'] : null;
        $inputArea = (isset($validated['leased_area']) && $validated['leased_area'] !== '' && $validated['leased_area'] > 0)
            ? $validated['leased_area']
            : null;

        $companyLand->update([
            'land_code'              => $validated['land_code'],
            'company_id'             => $validated['company_id'],
            'plot_id'                => $validated['plot_id'] ?? null,
            'zone_id'                => $validated['zone_id'] ?? null,
            'certificate_number'     => $validated['certificate_number'] ?? null,
            'cadastral_book_number'  => $validated['cadastral_book_number'] ?? null,
            'issue_date'             => $validated['issue_date'] ?? null,
            'issuing_authority'      => $validated['issuing_authority'] ?? 'UBND tỉnh Bắc Giang',
            'contract_number'        => $validated['contract_number'] ?? null,
            'contract_date'          => $validated['contract_date'] ?? null,
            'lease_start_date'       => $validated['lease_start_date'] ?? null,
            'lease_end_date'         => $validated['lease_end_date'] ?? null,
            'payment_type'           => $validated['payment_type'],
            'leased_area'            => $inputArea,
            'land_purpose'           => $validated['land_purpose'] ?? null,
            'building_density_limit' => $validated['building_density_limit'] ?? null,
            'status'                 => $validated['status'],
            'is_handed_over'         => $validated['is_handed_over'] ?? true,
            'description'            => $validated['description'] ?? null,
        ]);

        if ($geomJson) {
            if (empty($inputArea)) {
                DB::statement("
                    UPDATE company_lands 
                    SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326),
                        calculated_area = ROUND(ST_Area(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography)::numeric, 2),
                        leased_area = ROUND(ST_Area(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography)::numeric, 2)
                    WHERE id = ?
                ", [$geomJson, $geomJson, $geomJson, $companyLand->id]);
            } else {
                DB::statement("
                    UPDATE company_lands 
                    SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326),
                        calculated_area = ROUND(ST_Area(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography)::numeric, 2)
                    WHERE id = ?
                ", [$geomJson, $geomJson, $companyLand->id]);
            }
        }

        return redirect()->back()->with('success', "Cập nhật thửa đất [{$companyLand->land_code}] thành công!");
    }

    /**
     * 4. Xóa thửa đất doanh nghiệp
     */
    public function destroy($id)
    {
        $companyLand = CompanyLand::findOrFail($id);
        $code = $companyLand->land_code;
        $companyLand->delete();

        return redirect()->back()->with('success', "Đã xóa thửa đất [{$code}]!");
    }
}
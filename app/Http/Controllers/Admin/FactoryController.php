<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Factory;
use App\Models\Plot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FactoryController extends Controller
{
    // 1. Danh sách Nhà xưởng
    public function index()
    {
        $user = Auth::user();

        // 🌟 Lấy danh sách xưởng kèm tên công ty, mã lô đất và convert geom sang GeoJSON bằng Eloquent
        $factories = Factory::query()
            ->leftJoin('companies', 'factories.company_id', '=', 'companies.id')
            ->leftJoin('plots', 'factories.plot_id', '=', 'plots.id')
            ->select(
                'factories.*',
                DB::raw('ST_AsGeoJSON(factories.geom) as geojson'),
                'companies.name as company_name',
                'plots.code as plot_code'
            )
            ->orderBy('factories.id', 'desc')
            ->get();

        // Dữ liệu dropdown để chọn khi Thêm/Sửa
        $companies = Company::select('id', 'name')->orderBy('name', 'asc')->get();
        
        // Sử dụng DB::table nếu chưa tạo Model Plot, hoặc Plot::select nếu đã có Model
        $plots = class_exists(Plot::class) 
            ? Plot::select('id', 'code')->orderBy('code', 'asc')->get()
            : DB::table('plots')->select('id', 'code')->get();

        return Inertia::render('Admin/Factories/Index', [
            'auth'      => ['user' => $user],
            'factories' => $factories,
            'companies' => $companies,
            'plots'     => $plots,
        ]);
    }

    // 2. Thêm Nhà xưởng mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'       => 'required|string|max:50|unique:factories,code',
            'area'       => 'nullable|numeric|min:0',
            'status'     => 'required|in:available,rented,maintenance',
            'plot_id'    => 'nullable|exists:plots,id',
            'company_id' => 'nullable|exists:companies,id',
            'geojson'    => 'nullable|string',
        ], [
            'code.required' => 'Mã nhà xưởng là bắt buộc',
            'code.unique'   => 'Mã nhà xưởng đã tồn tại',
        ]);

        $geoJson = $request->geojson;

        // 🌟 Thêm mới bằng Eloquent Model (Kích hoạt Audit Log Event CREATED)
        Factory::create([
            'code'       => $validated['code'],
            'area'       => $validated['area'] ?? null,
            'status'     => $validated['status'],
            'plot_id'    => $validated['plot_id'] ?? null,
            'company_id' => $validated['status'] === 'rented' ? ($validated['company_id'] ?? null) : null,
            'geom'       => $geoJson ? DB::raw("ST_GeomFromGeoJSON('$geoJson')") : null,
        ]);

        return redirect()->back()->with('success', 'Thêm nhà xưởng thành công!');
    }

    // 3. Cập nhật Nhà xưởng
    public function update(Request $request, $id)
    {
        $factory = Factory::findOrFail($id);

        $validated = $request->validate([
            'code'       => 'required|string|max:50|unique:factories,code,' . $factory->id,
            'area'       => 'nullable|numeric|min:0',
            'status'     => 'required|in:available,rented,maintenance',
            'plot_id'    => 'nullable|exists:plots,id',
            'company_id' => 'nullable|exists:companies,id',
            'geojson'    => 'nullable|string',
        ], [
            'code.required' => 'Mã nhà xưởng là bắt buộc',
            'code.unique'   => 'Mã nhà xưởng đã tồn tại',
        ]);

        $geoJson = $request->geojson;

        // 🌟 Cập nhật bằng Eloquent Model (Kích hoạt Audit Log Event UPDATED kèm so sánh dữ liệu cũ/mới)
        $factory->update([
            'code'       => $validated['code'],
            'area'       => $validated['area'] ?? null,
            'status'     => $validated['status'],
            'plot_id'    => $validated['plot_id'] ?? null,
            'company_id' => $validated['status'] === 'rented' ? ($validated['company_id'] ?? null) : null,
            'geom'       => $geoJson ? DB::raw("ST_GeomFromGeoJSON('$geoJson')") : null,
        ]);

        return redirect()->back()->with('success', 'Cập nhật nhà xưởng thành công!');
    }

    // 4. Xóa Nhà xưởng
    public function destroy($id)
    {
        $factory = Factory::findOrFail($id);

        // 🌟 Xóa bằng Eloquent Model (Kích hoạt Audit Log Event DELETED)
        $factory->delete();

        return redirect()->back()->with('success', 'Đã xóa nhà xưởng!');
    }
}
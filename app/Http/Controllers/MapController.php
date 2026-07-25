<?php

namespace App\Http\Controllers;

use App\Models\Factory;
use App\Models\Greenery;
use App\Models\Plot;
use App\Models\RainwaterManhole;
use App\Models\Road;
use App\Models\WaterBody;
use App\Models\WastewaterManhole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MapController extends Controller
{
    public function index()
    {
        // 1. TRUY VẤN DỮ LIỆU NHÀ XƯỞNG (Polygon) QUA ELOQUENT
        $factoriesRaw = Factory::query()
            ->leftJoin('companies', 'factories.company_id', '=', 'companies.id')
            ->leftJoin('plots', 'factories.plot_id', '=', 'plots.id')
            ->select(
                'factories.id',
                'factories.code',
                'factories.area',
                'factories.status',
                'factories.company_id',
                'companies.name as company_name',
                'companies.tax_code as company_tax_code',
                'companies.industry as company_industry',
                'companies.color as company_color',
                'companies.phone as company_phone',
                'companies.email as company_email',
                'plots.code as plot_code',
                DB::raw('ST_AsGeoJSON(factories.geom) as geojson')
            )
            ->get();

        $factoriesFeatures = $factoriesRaw->map(function ($item) {
            $geometry = $item->geojson ? json_decode($item->geojson) : null;

            return [
                'type' => 'Feature',
                'geometry' => $geometry,
                'properties' => [
                    'id'               => $item->id,
                    'code'             => $item->code,
                    'area'             => $item->area,
                    'status'           => $item->status,
                    'plot_code'        => $item->plot_code,
                    
                    'company_id'       => $item->company_id,
                    'company_name'     => $item->company_name ?? 'Đang trống / Chưa thuê',
                    'company_tax_code' => $item->company_tax_code ?? 'N/A',
                    'company_industry' => $item->company_industry ?? 'N/A',
                    'company_color'    => $item->company_color ?? 'N/A',
                    'company_phone'    => $item->company_phone ?? 'N/A',
                    'company_email'    => $item->company_email ?? 'N/A',
                ]
            ];
        });

        $factoriesGeoJson = [
            'type'     => 'FeatureCollection',
            'features' => $factoriesFeatures
        ];

        // 2. TRUY VẤN DỮ LIỆU HỐ GA NƯỚC THẢI (Point)
        $wastewaterManholes = WastewaterManhole::query()
            ->leftJoin('companies', 'wastewater_manholes.company_id', '=', 'companies.id')
            ->select(
                'wastewater_manholes.id',
                'wastewater_manholes.code',
                'wastewater_manholes.depth',
                'wastewater_manholes.pipe_diameter',
                'wastewater_manholes.status',
                'wastewater_manholes.note',
                'companies.name as company_name',
                DB::raw('ST_AsGeoJSON(wastewater_manholes.geom) as geojson')
            )
            ->whereNotNull('wastewater_manholes.geom')
            ->get();

        $wastewaterFeatures = $wastewaterManholes->map(function ($mh) {
            return [
                'type' => 'Feature',
                'geometry' => json_decode($mh->geojson),
                'properties' => [
                    'id'            => $mh->id,
                    'code'          => $mh->code,
                    'depth'         => $mh->depth,
                    'pipe_diameter' => $mh->pipe_diameter,
                    'status'        => $mh->status,
                    'note'          => $mh->note,
                    'company_name'  => $mh->company_name
                ]
            ];
        });

        // 3. TRUY VẤN DỮ LIỆU HỐ GA NƯỚC MƯA (Point)
        $rainwaterManholes = RainwaterManhole::query()
            ->leftJoin('companies', 'rainwater_manholes.company_id', '=', 'companies.id')
            ->select(
                'rainwater_manholes.id',
                'rainwater_manholes.code',
                'rainwater_manholes.depth',
                'rainwater_manholes.cover_material',
                'rainwater_manholes.status',
                'rainwater_manholes.note',
                'companies.name as company_name',
                DB::raw('ST_AsGeoJSON(rainwater_manholes.geom) as geojson')
            )
            ->whereNotNull('rainwater_manholes.geom')
            ->get();

        $rainwaterFeatures = $rainwaterManholes->map(function ($mh) {
            return [
                'type' => 'Feature',
                'geometry' => json_decode($mh->geojson),
                'properties' => [
                    'id'             => $mh->id,
                    'code'           => $mh->code,
                    'depth'          => $mh->depth,
                    'cover_material' => $mh->cover_material,
                    'status'         => $mh->status,
                    'note'           => $mh->note,
                    'company_name'   => $mh->company_name
                ]
            ];
        });

        // 4. TRUY VẤN DỮ LIỆU ĐƯỜNG GIAO THÔNG (LineString)
        $roadsQuery = class_exists(Road::class) ? Road::query() : DB::table('roads');
        $roads = $roadsQuery
            ->select(
                'id',
                'name',
                'right_of_way',
                'road_width',
                DB::raw('ST_AsGeoJSON(geom) as geojson')
            )
            ->whereNotNull('geom')
            ->get();

        $roadFeatures = $roads->map(function ($road) {
            return [
                'type' => 'Feature',
                'geometry' => json_decode($road->geojson),
                'properties' => [
                    'id'           => $road->id,
                    'name'         => $road->name,
                    'right_of_way' => $road->right_of_way,
                    'road_width'   => $road->road_width,
                ]
            ];
        });

        // 5. TRUY VẤN DỮ LIỆU LÔ ĐẤT (Polygon)
        $plots = Plot::query()
            ->select(
                'id',
                'code',
                'total_area',
                'description',
                DB::raw('ST_AsGeoJSON(geom) as geojson')
            )
            ->whereNotNull('geom')
            ->get();

        $plotFeatures = $plots->map(function ($plot) {
            return [
                'type' => 'Feature',
                'geometry' => json_decode($plot->geojson),
                'properties' => [
                    'id'          => $plot->id,
                    'code'        => $plot->code,
                    'total_area'  => $plot->total_area,
                    'description' => $plot->description,
                ]
            ];
        });

        // 6. CÂY XANH & MẶT NƯỚC
        $greeneryData = [
            'type' => 'FeatureCollection',
            'features' => Greenery::select('*', DB::raw('ST_AsGeoJSON(geom) as geojson'))->get()->map(function($item) {
                return [
                    'type' => 'Feature',
                    'geometry' => json_decode($item->geojson),
                    'properties' => [ 'code' => $item->code, 'area' => $item->area, 'description' => $item->description ]
                ];
            })
        ];

        $waterBodiesData = [
            'type' => 'FeatureCollection',
            'features' => WaterBody::select('*', DB::raw('ST_AsGeoJSON(geom) as geojson'))->get()->map(function($item) {
                return [
                    'type' => 'Feature',
                    'geometry' => json_decode($item->geojson),
                    'properties' => [ 'code' => $item->code, 'area' => $item->area, 'description' => $item->description ]
                ];
            })
        ];

        $user = Auth::user();

        // 7. TRUYỀN DỮ LIỆU SANG INERTIA MAP VIEW
        return Inertia::render('Map', [
            'auth' => [
                'user' => $user ? [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'company',
                ] : null,
            ],
            'factoriesData'          => $factoriesGeoJson,
            'wastewaterManholesData' => [
                'type'     => 'FeatureCollection',
                'features' => $wastewaterFeatures
            ],
            'rainwaterManholesData'  => [
                'type'     => 'FeatureCollection',
                'features' => $rainwaterFeatures
            ],
            'roadsData'              => [
                'type'     => 'FeatureCollection',
                'features' => $roadFeatures
            ],
            'plotsData'              => [
                'type'     => 'FeatureCollection',
                'features' => $plotFeatures
            ],
            'greeneryData'           => $greeneryData,
            'waterBodiesData'        => $waterBodiesData,
        ]);
    }
}
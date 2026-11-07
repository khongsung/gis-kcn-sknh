<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LightingCable extends Model
{
    use HasFactory;

    protected $table = 'lighting_cables';

    protected $fillable = [
        'zone_id',
        'cabinet_id',
        'road_id',
        'code',
        'name',
        'cable_spec',
        'installation_type',
        'depth_m',
        'conduit_type',
        'length_m',
        'status',
    ];

    protected $casts = [
        'depth_m'  => 'float',
        'length_m' => 'float',
    ];

    /* =========================================================
     * 1. RELATIONSHIPS (QUAN HỆ LIÊN KẾT)
     * ========================================================= */

    /**
     * Tuyến cáp thuộc một Khu công nghiệp
     */
    public function zone()
    {
        return $this->belongsTo(IndustrialZone::class, 'zone_id');
    }

    /**
     * Tuyến cáp xuất phát hoặc cấp nguồn từ một Tủ điện
     */
    public function cabinet()
    {
        return $this->belongsTo(LightingCabinet::class, 'cabinet_id');
    }

    /**
     * Tuyến cáp đi ngầm/nổi theo Tuyến đường giao thông
     */
    public function road()
    {
        return $this->belongsTo(Road::class, 'road_id');
    }

    /* =========================================================
     * 2. ACCESSORS & SPATIAL SCOPES (XỬ LÝ DỮ LIỆU GIS POSTGIS)
     * ========================================================= */

    /**
     * Scope trích xuất GeoJSON LineString và chiều dài thực tế (m)
     */
    public function scopeWithGeoJson($query)
    {
        return $query->addSelect([
            'lighting_cables.*',
            DB::raw('ST_AsGeoJSON(lighting_cables.geom) as geojson'),
            // Tự động đo lại chiều dài cáp theo hệ tọa độ địa lý mét
            DB::raw('ROUND(ST_Length(lighting_cables.geom::geography)::numeric, 2) as calculated_length_m')
        ]);
    }
}

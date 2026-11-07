<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LightingPole extends Model
{
    use HasFactory;

    protected $table = 'lighting_poles';

    protected $fillable = [
        'zone_id',
        'cabinet_id',
        'road_id',
        'code',
        'height_m',
        'pole_material',
        'arm_type',
        'lamp_type',
        'wattage',
        'status',
        'installation_date',
        'notes',
    ];

    protected $casts = [
        'height_m'          => 'float',
        'wattage'           => 'float',
        'installation_date' => 'date',
    ];

    /* =========================================================
     * 1. RELATIONSHIPS (QUAN HỆ LIÊN KẾT)
     * ========================================================= */

    /**
     * Thuộc về một Khu công nghiệp
     */
    public function zone()
    {
        return $this->belongsTo(IndustrialZone::class, 'zone_id');
    }

    /**
     * Thuộc quyền điều khiển đóng cắt của một Tủ điện
     */
    public function cabinet()
    {
        return $this->belongsTo(LightingCabinet::class, 'cabinet_id');
    }

    /**
     * Đặt dọc theo một Tuyến đường
     */
    public function road()
    {
        return $this->belongsTo(Road::class, 'road_id');
    }

    /* =========================================================
     * 2. ACCESSORS & SPATIAL SCOPES (XỬ LÝ DỮ LIỆU GIS POSTGIS)
     * ========================================================= */

    /**
     * Scope tự động trích xuất vĩ độ (Lat) và kinh độ (Lng)
     */
    public function scopeWithGeoJson($query)
    {
        return $query->addSelect([
            'lighting_poles.*',
            DB::raw('ST_AsGeoJSON(lighting_poles.geom) as geojson'),
            DB::raw('ST_Y(lighting_poles.geom) as lat'),
            DB::raw('ST_X(lighting_poles.geom) as lng'),
        ]);
    }
}

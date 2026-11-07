<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LightingCabinet extends Model
{
    use HasFactory;

    protected $table = 'lighting_cabinets';

    protected $fillable = [
        'zone_id',
        'road_id',
        'code',
        'name',
        'capacity_kva',
        'control_type',
        'power_source',
        'channels_count',
        'status',
        'installation_date',
        'notes',
    ];

    protected $casts = [
        'capacity_kva'      => 'float',
        'channels_count'    => 'integer',
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
     * Đặt trên một Tuyến đường (nếu có)
     */
    public function road()
    {
        return $this->belongsTo(Road::class, 'road_id');
    }

    /**
     * Quản lý cấp nguồn cho nhiều Cột đèn
     */
    public function poles()
    {
        return $this->hasMany(LightingPole::class, 'cabinet_id');
    }

    /**
     * Cấp nguồn cho nhiều Tuyến cáp nhánh
     */
    public function cables()
    {
        return $this->hasMany(LightingCable::class, 'cabinet_id');
    }

    /* =========================================================
     * 2. ACCESSORS & SPATIAL SCOPES (XỬ LÝ DỮ LIỆU GIS POSTGIS)
     * ========================================================= */

    /**
     * Scope tự động chuyển cột geom sang GeoJSON dạng text
     */
    public function scopeWithGeoJson($query)
    {
        return $query->addSelect([
            'lighting_cabinets.*',
            DB::raw('ST_AsGeoJSON(lighting_cabinets.geom) as geojson'),
            DB::raw('ST_Y(lighting_cabinets.geom) as lat'),
            DB::raw('ST_X(lighting_cabinets.geom) as lng'),
        ]);
    }
}

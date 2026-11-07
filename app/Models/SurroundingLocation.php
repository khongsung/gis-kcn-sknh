<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SurroundingLocation extends Model
{
    use HasFactory;

    protected $table = 'surrounding_locations';

    protected $fillable = [
        'zone_id',
        'code',
        'name',
        'category',
        'bg_color',
        'text_color',
        'icon',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* =========================================================
     * 1. RELATIONSHIPS
     * ========================================================= */

    /**
     * Thuộc về hoặc tiếp giáp với một Khu công nghiệp
     */
    public function zone()
    {
        return $this->belongsTo(IndustrialZone::class, 'zone_id');
    }

    /* =========================================================
     * 2. ACCESSORS & SPATIAL SCOPES
     * ========================================================= */

    /**
     * Scope tự động trích xuất GeoJSON và tọa độ Lat/Lng từ Point PostGIS
     */
    public function scopeWithGeoJson($query)
    {
        return $query->addSelect([
            'surrounding_locations.*',
            DB::raw('ST_AsGeoJSON(surrounding_locations.geom) as geojson'),
            DB::raw('ST_Y(surrounding_locations.geom) as lat'),
            DB::raw('ST_X(surrounding_locations.geom) as lng'),
        ]);
    }
}
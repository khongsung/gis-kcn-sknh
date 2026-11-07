<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndustrialZone extends Model
{
    use HasFactory;

    protected $table = 'industrial_zones';

    protected $fillable = [
        'code',
        'name',
        'province',
        'address',
        'center_lat',
        'center_lng',
        'default_zoom',
        'total_area',
        'description',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'total_area'   => 'float',
        'default_zoom' => 'integer',
    ];

	public function companies() { return $this->hasMany(Company::class, 'zone_id'); }
	public function wastewaterManholes() { return $this->hasMany(WastewaterManhole::class, 'zone_id'); }
	public function rainwaterManholes() { return $this->hasMany(RainwaterManhole::class, 'zone_id'); }
	public function greenery() { return $this->hasMany(Greenery::class, 'zone_id'); }
	public function waterBodies() { return $this->hasMany(WaterBody::class, 'zone_id'); }

    /**
     * Quan hệ 1-N với Lô đất
     */
    public function plots()
    {
        return $this->hasMany(Plot::class, 'zone_id');
    }

    public function factories()
    {
        return $this->hasMany(Factory::class, 'zone_id');
    }

    /**
     * Quan hệ 1-N với Ranh giới quy hoạch
     */
    public function boundaries()
    {
        return $this->hasMany(IndustrialZoneBoundary::class, 'zone_id');
    }

    /**
     * Quan hệ 1-N với Tuyến đường giao thông
     */
    public function roads()
    {
        return $this->hasMany(Road::class, 'zone_id');
    }

    public function surroundingLocations()
    {
        return $this->hasMany(SurroundingLocation::class, 'zone_id');
    }
}

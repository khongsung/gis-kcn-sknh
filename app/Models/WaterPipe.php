<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterPipe extends Model
{
    use HasFactory;

    protected $table = 'water_pipes';

    protected $fillable = [
        'zone_id',
        'road_id',
        'pipe_type',
        'code',
        'name',
        'start_manhole_id',
        'end_manhole_id',
        'cross_section',
        'diameter',
        'width',
        'height',
        'material',
        'invert_start_elevation',
        'invert_end_elevation',
        'slope',
        'status',
        'last_inspected_at',
        'description',
    ];

    protected $casts = [
        'diameter'               => 'integer',
        'width'                  => 'integer',
        'height'                 => 'integer',
        'invert_start_elevation' => 'float',
        'invert_end_elevation'   => 'float',
        'slope'                  => 'float',
        'last_inspected_at'      => 'date',
    ];

    public function zone()
    {
        return $this->belongsTo(IndustrialZone::class, 'zone_id');
    }

    public function road()
    {
        return $this->belongsTo(Road::class, 'road_id');
    }

    // Quan hệ lấy hố ga nước thải
    public function startWastewaterManhole()
    {
        return $this->belongsTo(WastewaterManhole::class, 'start_manhole_id');
    }

    public function endWastewaterManhole()
    {
        return $this->belongsTo(WastewaterManhole::class, 'end_manhole_id');
    }

    // Quan hệ lấy hố ga nước mưa
    public function startRainwaterManhole()
    {
        return $this->belongsTo(RainwaterManhole::class, 'start_manhole_id');
    }

    public function endRainwaterManhole()
    {
        return $this->belongsTo(RainwaterManhole::class, 'end_manhole_id');
    }
}
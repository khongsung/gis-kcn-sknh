<?php

namespace App\Models;

use Database\Factories\RoadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Road extends Model
{
    use HasFactory, LogsActivity; // 🌟 Tự động ghi Audit Log khi Thêm/Sửa/Xóa

    protected $table = 'roads';

    protected $fillable = [
    	'zone_id',
        'name',
        'right_of_way',
        'road_width',
        'geom',
    ];

    /**
     * Tuyến đường thuộc về một Khu công nghiệp
     */
    public function zone()
    {
        return $this->belongsTo(IndustrialZone::class, 'zone_id');
    }

    /**
     * Các tủ điện đặt trên tuyến đường này
     */
    public function lightingCabinets()
    {
        return $this->hasMany(LightingCabinet::class, 'road_id');
    }

    /**
     * Các cột đèn nằm dọc tuyến đường
     */
    public function lightingPoles()
    {
        return $this->hasMany(LightingPole::class, 'road_id');
    }

    /**
     * Các tuyến cáp chiếu sáng đi dọc tuyến đường
     */
    public function lightingCables()
    {
        return $this->hasMany(LightingCable::class, 'road_id');
    }
}
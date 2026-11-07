<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class WaterBody extends Model
{
    use HasFactory, LogsActivity; // 🌟 Tự động kích hoạt Audit Log khi Thêm/Sửa/Xóa

    protected $table = 'water_bodies';

    protected $fillable = [
        'code',
        'area',
        'description',
        'zone_id',
        'geom',
    ];

    public function zone()
    {
        return $this->belongsTo(IndustrialZone::class, 'zone_id');
    }
}
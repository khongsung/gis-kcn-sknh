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
        'name',
        'right_of_way',
        'road_width',
        'geom',
    ];
}
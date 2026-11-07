<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class Plot extends Model
{
    use HasFactory, LogsActivity; // 🌟 Tự động kích hoạt Audit Log khi Thêm/Sửa/Xóa

    protected $fillable = [
        'code',
        'total_area',
        'description',
        'zone_id',
        'geom',
    ];

    /**
     * Quan hệ với Khu công nghiệp
     */
    public function zone()
    {
        return $this->belongsTo(IndustrialZone::class, 'zone_id');
    }

    /**
     * Quan hệ 1-N: Lô đất chứa nhiều Nhà xưởng
     */
    public function factories(): HasMany
    {
        return $this->hasMany(Factory::class, 'plot_id');
    }

    // Hoặc quan hệ xuyên bảng: Lấy các công ty thông qua factories
    public function companies()
    {
        return $this->hasManyThrough(
            Company::class,
            Factory::class,
            'plot_id',    // Khóa ngoại trên bảng factories trỏ về plots
            'id',         // Khóa chính trên bảng companies
            'id',         // Khóa chính trên bảng plots
            'company_id'  // Khóa ngoại trên bảng factories trỏ về companies
        )->distinct();
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class Company extends Model
{
    use HasFactory, LogsActivity; // 🌟 Tự động kích hoạt ghi Audit Log khi Thêm/Sửa/Xóa

    protected $fillable = [
        'name',
        'tax_code',
        'industry',
        'color',
        'phone',
        'email',
        'description',
        'zone_id',
    ];

    public function zone()
    {
        return $this->belongsTo(IndustrialZone::class, 'zone_id');
    }

    /**
     * Quan hệ 1-N: Một công ty có thể sở hữu nhiều Nhà xưởng
     */
    public function factories(): HasMany
    {
        return $this->hasMany(Factory::class, 'company_id');
    }
}
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
        'geom',
    ];

    /**
     * Quan hệ 1-N: Lô đất chứa nhiều Nhà xưởng
     */
    public function factories(): HasMany
    {
        return $this->hasMany(Factory::class, 'plot_id');
    }

    /**
     * Quan hệ N-N: Lô đất liên kết với nhiều Công ty qua bảng trung gian company_plot
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_plot', 'plot_id', 'company_id')
                    ->withTimestamps();
    }
}
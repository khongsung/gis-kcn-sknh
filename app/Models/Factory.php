<?php

namespace App\Models;

use Database\Factories\FactoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class Factory extends Model
{
    use HasFactory, LogsActivity; // 🌟 Tự động kích hoạt ghi Audit Log khi Thêm/Sửa/Xóa

    protected $fillable = [
        'code',
        'area',
        'status',
        'plot_id',
        'company_id',
        'geom',
    ];

    /**
     * Chỉ định rõ ràng Factory khởi tạo cho Model này
     */
    protected static function newFactory()
    {
        return FactoryFactory::new();
    }

    /**
     * Quan hệ N-1: Nhà xưởng thuộc về một Công ty
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Quan hệ N-1: Nhà xưởng nằm trên một Lô đất
     */
    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class, 'plot_id');
    }
}
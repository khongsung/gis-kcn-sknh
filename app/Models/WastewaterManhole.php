<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class WastewaterManhole extends Model
{
    use HasFactory, LogsActivity; // 🌟 Kích hoạt Audit Log tự động khi Thêm/Sửa/Xóa

    protected $table = 'wastewater_manholes';

    protected $fillable = [
        'code',
        'company_id',
        'factory_id',
        'road_id',
        'depth',
        'elevation',
        'pipe_diameter',
        'status',
        'geom',
        'note',
    ];

    /**
     * Quan hệ N-1: Hố ga nước thải thuộc về một Công ty
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Quan hệ N-1: Hố ga nước thải thuộc về một Nhà xưởng
     */
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class, 'factory_id');
    }

    /**
     * Quan hệ N-1: Hố ga nước thải nằm trên Tuyến đường
     */
    public function road(): BelongsTo
    {
        return $this->belongsTo(Road::class, 'road_id');
    }
}
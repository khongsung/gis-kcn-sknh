<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class Company extends Model
{
    use HasFactory, LogsActivity; // 🌟 Tự động kích hoạt ghi Audit Log khi Thêm/Sửa/Xóa

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'tax_code',
        'total_leased_area',
        'investment_certificate_number',
        'investment_certificate_date',
        'lease_start_date',
        'lease_end_date',
        'industry',
        'main_industry',
        'country',
        'color',
        'phone',
        'email',
        'description',
        'notes',
        'zone_id',
    ];

    protected $casts = [
        'total_leased_area'           => 'float',
        'investment_certificate_date' => 'date',
        'lease_start_date'            => 'date',
        'lease_end_date'              => 'date',
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

    // Quan hệ với các thửa đất thứ cấp
    public function lands()
    {
        return $this->hasMany(CompanyLand::class, 'company_id');
    }
}
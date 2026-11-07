<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndustrialZoneBoundary extends Model
{
    use HasFactory;

    protected $table = 'industrial_zone_boundaries';

    protected $fillable = [
        'zone_id',
        'code',
        'name',
        'decision_number',
        'approval_date',
        'total_planning_area',
        'legal_basis',
        'fill_color',
    ];

    /**
     * Quan hệ trỏ về Khu công nghiệp
     */
    public function zone()
    {
        return $this->belongsTo(IndustrialZone::class, 'zone_id');
    }
}

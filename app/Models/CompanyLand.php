<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyLand extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_id',
        'plot_id',
        'company_id',
        'land_code',
        'certificate_number',
        'cadastral_book_number',
        'issue_date',
        'issuing_authority',
        'contract_number',
        'contract_date',
        'lease_start_date',
        'lease_end_date',
        'payment_type',
        'leased_area',
        'calculated_area',
        'land_purpose',
        'building_density_limit',
        'status',
        'is_handed_over',
        'description',
    ];

    protected $casts = [
        'issue_date'             => 'date',
        'contract_date'          => 'date',
        'lease_start_date'       => 'date',
        'lease_end_date'         => 'date',
        'leased_area'            => 'float',
        'calculated_area'        => 'float',
        'building_density_limit' => 'float',
        'is_handed_over'         => 'boolean',
    ];

    public function plot()
    {
        return $this->belongsTo(Plot::class, 'plot_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function zone()
    {
        return $this->belongsTo(IndustrialZone::class, 'zone_id');
    }
}

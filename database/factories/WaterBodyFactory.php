<?php

namespace Database\Factories;

use App\Models\WaterBody;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class WaterBodyFactory extends Factory
{
    protected $model = WaterBody::class;

    public function definition(): array
    {
        return [
            'code'        => 'MN-' . $this->faker->unique()->numberBetween(100, 999),
            'area'        => $this->faker->randomFloat(2, 1000, 20000), // Diện tích từ 1,000m² - 20,000m²
            'description' => 'Hồ điều hòa trung tâm thoát nước mưa KCN',
            // Tọa độ Polygon mẫu chuẩn PostGIS
            'geom'        => DB::raw("ST_GeomFromGeoJSON('{\"type\":\"Polygon\",\"coordinates\":[[[106.175,21.245],[106.178,21.245],[106.178,21.247],[106.175,21.247],[106.175,21.245]]]}')"),
        ];
    }
}
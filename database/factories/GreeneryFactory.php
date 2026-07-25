<?php

namespace Database\Factories;

use App\Models\Greenery;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class GreeneryFactory extends Factory
{
    protected $model = Greenery::class;

    public function definition(): array
    {
        return [
            'code'        => 'CX-' . $this->faker->unique()->numberBetween(100, 999),
            'area'        => $this->faker->randomFloat(2, 500, 5000), // Diện tích 500m² - 5000m²
            'description' => 'Dải cây xanh cách ly tuyến đường chính KCN',
            // Tọa độ Polygon mẫu chuẩn PostGIS
            'geom'        => DB::raw("ST_GeomFromGeoJSON('{\"type\":\"Polygon\",\"coordinates\":[[[106.171,21.241],[106.173,21.241],[106.173,21.242],[106.171,21.242],[106.171,21.241]]]}')"),
        ];
    }
}
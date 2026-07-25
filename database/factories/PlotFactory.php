<?php

namespace Database\Factories;

use App\Models\Plot;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlotFactory extends Factory
{
    protected $model = Plot::class;

    public function definition(): array
    {
        return [
            'code'        => 'CN-' . $this->faker->unique()->numberBetween(10, 99),
            'total_area'  => $this->faker->randomFloat(2, 5000, 50000), // Diện tích từ 5,000 m² - 50,000 m²
            'description' => $this->faker->sentence(),
            // Tọa độ mẫu dạng GeoJSON Polygon vuông
            'geom'        => \Illuminate\Support\Facades\DB::raw("ST_GeomFromGeoJSON('{\"type\":\"Polygon\",\"coordinates\":[[[106.17,21.24],[106.18,21.24],[106.18,21.25],[106.17,21.25],[106.17,21.24]]]}')"),
        ];
    }
}
<?php

namespace Database\Factories;

use App\Models\Road;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class RoadFactory extends Factory
{
    protected $model = Road::class;

    public function definition(): array
    {
        return [
            'name'         => 'Đường ' . $this->faker->streetName(),
            'right_of_way' => $this->faker->randomElement([20.5, 25.0, 31.0, 42.0]), // Chỉ giới đường đỏ (m)
            'road_width'   => $this->faker->randomElement([10.5, 15.0, 21.0]),       // Mặt đường (m)
            // Tọa độ LineString GeoJSON mẫu chuẩn PostGIS
            'geom'         => DB::raw("ST_GeomFromGeoJSON('{\"type\":\"LineString\",\"coordinates\":[[106.171,21.241],[106.175,21.243],[106.179,21.245]]}')"),
        ];
    }
}
<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Factory as FactoryModel;
use App\Models\Plot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class FactoryFactory extends Factory
{
    // Bắt buộc trỏ đúng tên alias của Model
    protected $model = FactoryModel::class;

    public function definition(): array
    {
        return [
            'code'       => 'NX-' . $this->faker->unique()->numberBetween(100, 999),
            'area'       => $this->faker->randomFloat(2, 1000, 8000),
            'status'     => $this->faker->randomElement(['available', 'rented', 'under_construction']),
            'plot_id'    => Plot::factory(),
            'company_id' => Company::factory(),
            'geom'       => DB::raw("ST_GeomFromGeoJSON('{\"type\":\"Polygon\",\"coordinates\":[[[106.172,21.242],[106.174,21.242],[106.174,21.244],[106.172,21.244],[106.172,21.242]]]}')"),
        ];
    }
}
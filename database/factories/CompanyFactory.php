<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->company(),
            'tax_code'    => $this->faker->unique()->numerify('010#######'),
            'industry'    => 'Linh kiện điện tử',
            'color'       => '#2563eb',
            'phone'       => $this->faker->phoneNumber(),
            'email'       => $this->faker->safeEmail(),
            'description' => $this->faker->sentence(),
        ];
    }
}
<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'area_id' => Area::factory(),
            'nome' => ucfirst(fake()->unique()->word().' '.fake()->word()),
            'descricao' => fake()->sentence(),
            'ativo' => true,
        ];
    }
}

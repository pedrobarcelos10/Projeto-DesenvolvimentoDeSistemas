<?php

namespace Database\Factories;

use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->company(),
            'sigla' => fake()->unique()->lexify('???'),
            'descricao' => fake()->sentence(),
            'recebe_chamados' => true,
            'ativo' => true,
        ];
    }

    public function naoRecebeChamados(): static
    {
        return $this->state(['recebe_chamados' => false]);
    }

    public function inativa(): static
    {
        return $this->state(['ativo' => false]);
    }
}

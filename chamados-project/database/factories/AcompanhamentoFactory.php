<?php

namespace Database\Factories;

use App\Models\Acompanhamento;
use App\Models\Chamado;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Acompanhamento>
 */
class AcompanhamentoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'chamado_id' => Chamado::factory(),
            'usuario_id' => User::factory(),
            'conteudo' => fake()->paragraph(),
            'interno' => false,
        ];
    }

    public function interno(): static
    {
        return $this->state(['interno' => true]);
    }
}

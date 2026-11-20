<?php

namespace Database\Factories;

use App\Enums\PrioridadeChamado;
use App\Enums\TipoChamado;
use App\Models\Categoria;
use App\Models\Formulario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Formulario>
 */
class FormularioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'categoria_id' => Categoria::factory(),
            'nome' => ucfirst(fake()->unique()->word().' '.fake()->word()),
            'descricao' => fake()->sentence(),
            'tipo_chamado' => TipoChamado::Requisicao,
            'prioridade_padrao' => PrioridadeChamado::Media,
            'ativo' => true,
        ];
    }
}

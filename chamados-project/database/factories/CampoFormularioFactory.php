<?php

namespace Database\Factories;

use App\Enums\TipoCampo;
use App\Models\CampoFormulario;
use App\Models\Formulario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CampoFormulario>
 */
class CampoFormularioFactory extends Factory
{
    public function definition(): array
    {
        $rotulo = ucfirst(fake()->unique()->word().' '.fake()->word());

        return [
            'formulario_id' => Formulario::factory(),
            'rotulo' => $rotulo,
            'chave' => Str::snake(Str::ascii($rotulo)),
            'tipo' => TipoCampo::Texto,
            'opcoes' => null,
            'obrigatorio' => false,
            'ordem' => 0,
        ];
    }

    /** @param  list<string>  $opcoes */
    public function lista(array $opcoes = ['Opção A', 'Opção B']): static
    {
        return $this->state(['tipo' => TipoCampo::Lista, 'opcoes' => $opcoes]);
    }

    public function obrigatorio(): static
    {
        return $this->state(['obrigatorio' => true]);
    }
}

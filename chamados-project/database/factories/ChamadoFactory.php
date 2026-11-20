<?php

namespace Database\Factories;

use App\Enums\PrioridadeChamado;
use App\Enums\StatusChamado;
use App\Enums\TipoChamado;
use App\Models\Area;
use App\Models\Chamado;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Para testes e dados de exemplo. Na aplicação, use App\Actions\Chamados\AbrirChamado.
 *
 * @extends Factory<Chamado>
 */
class ChamadoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titulo' => rtrim(fake()->sentence(5), '.'),
            'descricao' => fake()->paragraph(),
            'tipo' => fake()->randomElement(TipoChamado::cases()),
            'status' => StatusChamado::Aberto,
            'prioridade' => fake()->randomElement(PrioridadeChamado::cases()),
            'requerente_id' => User::factory(),
            'area_id' => Area::factory(),
        ];
    }

    public function status(StatusChamado $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'solucionado_em' => in_array($status, [StatusChamado::Solucionado, StatusChamado::Fechado], true) ? now() : null,
            'fechado_em' => in_array($status, [StatusChamado::Fechado, StatusChamado::Cancelado], true) ? now() : null,
        ]);
    }
}

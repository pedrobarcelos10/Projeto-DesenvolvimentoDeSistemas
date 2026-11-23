<?php

namespace Tests\Feature\Chamados;

use App\Actions\Chamados\AlterarStatusChamado;
use App\Enums\StatusChamado;
use App\Exceptions\ChamadoException;
use App\Livewire\Chamados\Exibir as ExibirChamado;
use App\Models\Area;
use App\Models\Chamado;
use App\Models\Secao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AceiteSolucaoTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    private User $requerente;

    private User $atendente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->area = Area::factory()->create();
        $this->requerente = User::factory()->create();
        $this->atendente = User::factory()->atendente(Secao::factory()->for($this->area)->create())->create();
    }

    private function chamado(StatusChamado $status, ?User $requerente = null): Chamado
    {
        return Chamado::factory()->status($status)->create([
            'area_id' => $this->area->id,
            'requerente_id' => ($requerente ?? $this->requerente)->id,
        ]);
    }

    public function test_somente_o_requerente_pode_aceitar_a_solucao(): void
    {
        $chamado = $this->chamado(StatusChamado::Solucionado);
        $admin = User::factory()->administrador()->create();

        $this->assertTrue($this->requerente->can('alterarStatus', [$chamado, StatusChamado::Fechado]));
        $this->assertFalse($this->atendente->can('alterarStatus', [$chamado, StatusChamado::Fechado]));
        $this->assertFalse($admin->can('alterarStatus', [$chamado, StatusChamado::Fechado]));
        $this->assertFalse($this->atendente->can('avaliarSolucao', $chamado));
    }

    public function test_a_action_recusa_aceite_feito_por_quem_nao_abriu_o_chamado(): void
    {
        $chamado = $this->chamado(StatusChamado::Solucionado);

        $this->expectException(ChamadoException::class);

        app(AlterarStatusChamado::class)->executar($chamado, StatusChamado::Fechado, $this->atendente);
    }

    public function test_atendente_que_abriu_o_proprio_chamado_pode_aceitar_a_solucao(): void
    {
        $chamado = $this->chamado(StatusChamado::Solucionado, $this->atendente);

        $this->assertTrue($this->atendente->can('alterarStatus', [$chamado, StatusChamado::Fechado]));

        app(AlterarStatusChamado::class)->executar($chamado, StatusChamado::Fechado, $this->atendente);

        $this->assertSame(StatusChamado::Fechado, $chamado->refresh()->status);
    }

    public function test_requerente_aceita_a_solucao_pela_tela_do_chamado(): void
    {
        $chamado = $this->chamado(StatusChamado::Solucionado);

        Livewire::actingAs($this->requerente)
            ->test(ExibirChamado::class, ['chamado' => $chamado])
            ->assertSee('Aceitar solução')
            ->call('prepararStatus', StatusChamado::Fechado->value)
            ->call('confirmarStatus')
            ->assertHasNoErrors();

        $this->assertSame(StatusChamado::Fechado, $chamado->refresh()->status);
        $this->assertNotNull($chamado->fechado_em);
    }

    public function test_atendente_ve_o_chamado_aguardando_o_requerente_e_so_pode_retomar(): void
    {
        $chamado = $this->chamado(StatusChamado::Solucionado);

        Livewire::actingAs($this->atendente)
            ->test(ExibirChamado::class, ['chamado' => $chamado])
            ->assertSee('Aguardando '.$this->requerente->name.' avaliar a solução.')
            ->assertDontSee('Aceitar solução')
            ->assertSee('Retomar atendimento')
            ->call('prepararStatus', StatusChamado::Fechado->value)
            ->call('confirmarStatus')
            ->assertForbidden();

        $this->assertSame(StatusChamado::Solucionado, $chamado->refresh()->status);
    }

    public function test_atendente_pode_encerrar_diretamente_um_chamado_em_andamento(): void
    {
        $chamado = $this->chamado(StatusChamado::EmAtendimento);

        $this->assertFalse($this->requerente->can('alterarStatus', [$chamado, StatusChamado::Fechado]));

        Livewire::actingAs($this->atendente)
            ->test(ExibirChamado::class, ['chamado' => $chamado])
            ->call('prepararStatus', StatusChamado::Fechado->value)
            ->call('confirmarStatus')
            ->assertHasErrors(['comentario' => 'required'])
            ->set('comentario', 'Solicitação atendida por telefone.')
            ->call('confirmarStatus')
            ->assertHasNoErrors();

        $this->assertSame(StatusChamado::Fechado, $chamado->refresh()->status);
    }

    public function test_administrador_pode_encerrar_diretamente(): void
    {
        $chamado = $this->chamado(StatusChamado::Aberto);
        $admin = User::factory()->administrador()->create();

        $this->assertTrue($admin->can('alterarStatus', [$chamado, StatusChamado::Fechado]));
    }
}

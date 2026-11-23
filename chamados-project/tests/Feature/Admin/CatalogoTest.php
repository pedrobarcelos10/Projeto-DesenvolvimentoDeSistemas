<?php

namespace Tests\Feature\Admin;

use App\Actions\Formularios\SalvarFormulario;
use App\Enums\TipoCampo;
use App\Livewire\Admin\Categorias;
use App\Livewire\Admin\Estrutura;
use App\Livewire\Admin\Formularios\Editar as EditarFormulario;
use App\Livewire\Admin\Formularios\Index as ListarFormularios;
use App\Models\Area;
use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\Formulario;
use App\Models\Secao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->administrador()->create();
    }

    public function test_administrador_cria_area_e_secao(): void
    {
        $tela = Livewire::actingAs($this->admin)
            ->test(Estrutura::class)
            ->call('novaArea')
            ->set('areaNome', 'Jurídico')
            ->set('areaSigla', 'jur')
            ->set('areaRecebeChamados', true)
            ->call('salvarArea')
            ->assertHasNoErrors();

        $area = Area::where('sigla', 'JUR')->firstOrFail();
        $this->assertTrue($area->recebe_chamados);

        $tela->call('novaSecao', $area->id)
            ->set('secaoNome', 'Contratos')
            ->call('salvarSecao')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('secoes', ['area_id' => $area->id, 'nome' => 'Contratos']);
    }

    public function test_area_em_uso_nao_e_excluida(): void
    {
        $secao = Secao::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(Estrutura::class)
            ->call('excluirArea', $secao->area_id);

        $this->assertModelExists($secao->area);
    }

    public function test_administrador_cria_subcategoria(): void
    {
        $pai = Categoria::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(Categorias::class)
            ->call('nova', $pai->id)
            ->assertSet('areaId', (string) $pai->area_id)
            ->set('nome', 'Scanner')
            ->call('salvar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categorias', ['nome' => 'Scanner', 'categoria_pai_id' => $pai->id, 'area_id' => $pai->area_id]);
    }

    public function test_subcategoria_nao_pode_ser_pai(): void
    {
        $pai = Categoria::factory()->create();
        $filha = Categoria::factory()->create(['area_id' => $pai->area_id, 'categoria_pai_id' => $pai->id]);

        Livewire::actingAs($this->admin)
            ->test(Categorias::class)
            ->call('nova')
            ->set('areaId', (string) $pai->area_id)
            ->set('categoriaPaiId', (string) $filha->id)
            ->set('nome', 'Neta')
            ->call('salvar')
            ->assertHasErrors(['categoriaPaiId']);
    }

    public function test_formulario_e_salvo_com_campos_em_ordem(): void
    {
        $categoria = Categoria::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(EditarFormulario::class)
            ->set('nome', 'Troca de toner')
            ->set('categoriaId', (string) $categoria->id)
            ->set('campos.0.rotulo', 'Modelo da impressora')
            ->call('adicionarCampo')
            ->set('campos.1.rotulo', 'Cor do toner')
            ->set('campos.1.tipo', TipoCampo::Lista->value)
            ->call('salvar')
            ->assertHasErrors(['campos.1.opcoes'])
            ->set('campos.1.opcoes', "Preto\nCiano\n\nPreto")
            ->call('moverCampo', 1, -1)
            ->call('salvar')
            ->assertHasNoErrors();

        $formulario = Formulario::where('nome', 'Troca de toner')->firstOrFail();
        $campos = $formulario->campos;

        $this->assertSame(['cor_do_toner', 'modelo_da_impressora'], $campos->pluck('chave')->all());
        $this->assertSame(['Preto', 'Ciano'], $campos->first()->opcoes);
    }

    public function test_campo_removido_some_e_chave_dos_mantidos_nao_muda(): void
    {
        $salvar = app(SalvarFormulario::class);
        $categoria = Categoria::factory()->create();
        $dados = ['categoria_id' => $categoria->id, 'nome' => 'Form', 'tipo_chamado' => 'requisicao', 'prioridade_padrao' => 'media', 'ativo' => true];

        $formulario = $salvar->executar($dados, [
            ['rotulo' => 'Primeiro', 'tipo' => 'texto'],
            ['rotulo' => 'Segundo', 'tipo' => 'texto'],
        ]);

        $segundo = $formulario->campos->last();

        $formulario = $salvar->executar($dados, [
            ['id' => $segundo->id, 'rotulo' => 'Segundo (renomeado)', 'tipo' => 'texto'],
        ], $formulario);

        $this->assertCount(1, $formulario->campos);
        $this->assertSame('segundo', $formulario->campos->first()->chave);
        $this->assertSame(0, $formulario->campos->first()->ordem);
    }

    public function test_formulario_com_chamados_nao_e_excluido(): void
    {
        $formulario = Formulario::factory()->create();
        Chamado::factory()->create(['formulario_id' => $formulario->id, 'area_id' => $formulario->categoria->area_id]);

        Livewire::actingAs($this->admin)
            ->test(ListarFormularios::class)
            ->call('excluir', $formulario->id);

        $this->assertModelExists($formulario);
    }
}

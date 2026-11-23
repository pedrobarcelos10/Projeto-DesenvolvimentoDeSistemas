<?php

namespace App\Livewire\Admin;

use App\Exceptions\AdministracaoException;
use App\Models\Area;
use App\Models\Secao;
use Closure;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Estrutura organizacional: áreas e suas seções.
 * Itens em uso não são excluídos, apenas desativados.
 */
#[Title('Áreas e seções')]
class Estrutura extends Component
{
    // Formulário de área
    #[Locked]
    public ?int $areaId = null;

    public string $areaNome = '';

    public string $areaSigla = '';

    public string $areaDescricao = '';

    public bool $areaRecebeChamados = false;

    public bool $areaAtiva = true;

    // Formulário de seção
    #[Locked]
    public ?int $secaoId = null;

    public string $secaoAreaId = '';

    public string $secaoNome = '';

    public string $secaoDescricao = '';

    public bool $secaoAtiva = true;

    /** @return Collection<int, Area> */
    #[Computed]
    public function areas(): Collection
    {
        return Area::query()
            ->withCount(['categorias', 'chamados'])
            ->with(['secoes' => fn ($q) => $q->withCount('usuarios')->orderBy('nome')])
            ->orderByDesc('ativo')
            ->orderBy('nome')
            ->get();
    }

    public function novaArea(): void
    {
        $this->reset('areaId', 'areaNome', 'areaSigla', 'areaDescricao', 'areaRecebeChamados', 'areaAtiva');
        $this->resetErrorBag();
        Flux::modal('area')->show();
    }

    public function editarArea(int $id): void
    {
        $area = Area::findOrFail($id);

        $this->areaId = $area->id;
        $this->areaNome = $area->nome;
        $this->areaSigla = (string) $area->sigla;
        $this->areaDescricao = (string) $area->descricao;
        $this->areaRecebeChamados = $area->recebe_chamados;
        $this->areaAtiva = $area->ativo;
        $this->resetErrorBag();

        Flux::modal('area')->show();
    }

    public function salvarArea(): void
    {
        $this->areaSigla = mb_strtoupper(trim($this->areaSigla));

        $dados = $this->validate([
            'areaNome' => ['required', 'string', 'max:255'],
            'areaSigla' => ['nullable', 'string', 'max:20', Rule::unique('areas', 'sigla')->ignore($this->areaId)],
            'areaDescricao' => ['nullable', 'string', 'max:2000'],
            'areaRecebeChamados' => ['boolean'],
            'areaAtiva' => ['boolean'],
        ], [], ['areaNome' => 'nome', 'areaSigla' => 'sigla', 'areaDescricao' => 'descrição']);

        Area::updateOrCreate(['id' => $this->areaId], [
            'nome' => $dados['areaNome'],
            'sigla' => $dados['areaSigla'] ?: null,
            'descricao' => $dados['areaDescricao'] ?: null,
            'recebe_chamados' => $dados['areaRecebeChamados'],
            'ativo' => $dados['areaAtiva'],
        ]);

        Flux::modal('area')->close();
        Flux::toast(variant: 'success', text: $this->areaId ? 'Área atualizada.' : 'Área criada.');
        unset($this->areas);
    }

    public function novaSecao(int $areaId): void
    {
        $this->reset('secaoId', 'secaoNome', 'secaoDescricao', 'secaoAtiva');
        $this->secaoAreaId = (string) $areaId;
        $this->resetErrorBag();
        Flux::modal('secao')->show();
    }

    public function editarSecao(int $id): void
    {
        $secao = Secao::findOrFail($id);

        $this->secaoId = $secao->id;
        $this->secaoAreaId = (string) $secao->area_id;
        $this->secaoNome = $secao->nome;
        $this->secaoDescricao = (string) $secao->descricao;
        $this->secaoAtiva = $secao->ativo;
        $this->resetErrorBag();

        Flux::modal('secao')->show();
    }

    public function salvarSecao(): void
    {
        $dados = $this->validate([
            'secaoAreaId' => ['required', Rule::exists('areas', 'id')],
            'secaoNome' => [
                'required', 'string', 'max:255',
                Rule::unique('secoes', 'nome')->where('area_id', $this->secaoAreaId)->ignore($this->secaoId),
            ],
            'secaoDescricao' => ['nullable', 'string', 'max:2000'],
            'secaoAtiva' => ['boolean'],
        ], [], ['secaoAreaId' => 'área', 'secaoNome' => 'nome', 'secaoDescricao' => 'descrição']);

        Secao::updateOrCreate(['id' => $this->secaoId], [
            'area_id' => (int) $dados['secaoAreaId'],
            'nome' => $dados['secaoNome'],
            'descricao' => $dados['secaoDescricao'] ?: null,
            'ativo' => $dados['secaoAtiva'],
        ]);

        Flux::modal('secao')->close();
        Flux::toast(variant: 'success', text: $this->secaoId ? 'Seção atualizada.' : 'Seção criada.');
        unset($this->areas);
    }

    public function excluirArea(int $id): void
    {
        $area = Area::findOrFail($id);

        $this->excluir($area->motivoParaNaoExcluir(), "a área \"{$area->nome}\"", fn () => $area->delete());
    }

    public function excluirSecao(int $id): void
    {
        $secao = Secao::findOrFail($id);

        $this->excluir($secao->motivoParaNaoExcluir(), "a seção \"{$secao->nome}\"", fn () => $secao->delete());
    }

    private function excluir(?string $motivo, string $descricao, Closure $acao): void
    {
        if ($motivo !== null) {
            Flux::toast(variant: 'danger', text: AdministracaoException::emUso($descricao, $motivo)->getMessage());

            return;
        }

        $acao();

        Flux::toast(variant: 'success', text: ucfirst($descricao).' foi excluída.');
        unset($this->areas);
    }

    public function render(): View
    {
        return view('livewire.admin.estrutura');
    }
}

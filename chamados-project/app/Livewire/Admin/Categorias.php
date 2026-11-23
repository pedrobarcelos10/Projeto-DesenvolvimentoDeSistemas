<?php

namespace App\Livewire\Admin;

use App\Exceptions\AdministracaoException;
use App\Models\Area;
use App\Models\Categoria;
use App\Models\Secao;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Catálogo de serviços: categorias (e subcategorias) que direcionam o chamado
 * para uma área e, opcionalmente, para uma seção padrão.
 * A hierarquia tem dois níveis: "Hardware > Impressora".
 *
 * @property-read bool $temSubcategorias
 */
#[Title('Categorias')]
class Categorias extends Component
{
    #[Url(as: 'area', except: '')]
    public string $filtroArea = '';

    #[Locked]
    public ?int $categoriaId = null;

    public string $areaId = '';

    public string $secaoId = '';

    public string $categoriaPaiId = '';

    public string $nome = '';

    public string $descricao = '';

    public bool $ativo = true;

    /** @return Collection<int, Area> */
    #[Computed]
    public function areas(): Collection
    {
        return Area::query()->orderBy('nome')->get();
    }

    /**
     * Categorias principais com as subcategorias logo abaixo.
     *
     * @return Collection<int, Categoria>
     */
    #[Computed]
    public function categorias(): Collection
    {
        $todas = Categoria::query()
            ->with(['area', 'secao'])
            ->withCount(['formularios', 'chamados'])
            ->when($this->filtroArea !== '', fn ($q) => $q->where('area_id', $this->filtroArea))
            ->orderBy('nome')
            ->get();

        $filhas = $todas->whereNotNull('categoria_pai_id')->groupBy('categoria_pai_id');

        return $todas
            ->whereNull('categoria_pai_id')
            ->sortBy(fn (Categoria $c) => $c->area->nome.' '.$c->nome)
            ->flatMap(fn (Categoria $pai) => [$pai, ...($filhas[$pai->id] ?? [])])
            ->values();
    }

    /** @return Collection<int, Secao> */
    #[Computed]
    public function secoesDaArea(): Collection
    {
        return $this->areaId === '' ? collect() : Secao::where('area_id', $this->areaId)->orderBy('nome')->get();
    }

    /**
     * Possíveis categorias pai: principais da mesma área, exceto a própria.
     *
     * @return Collection<int, Categoria>
     */
    #[Computed]
    public function paisPossiveis(): Collection
    {
        if ($this->areaId === '') {
            return collect();
        }

        return Categoria::query()
            ->where('area_id', $this->areaId)
            ->whereNull('categoria_pai_id')
            ->when($this->categoriaId, fn ($q) => $q->whereKeyNot($this->categoriaId))
            ->orderBy('nome')
            ->get();
    }

    #[Computed]
    public function temSubcategorias(): bool
    {
        return $this->categoriaId !== null && Categoria::where('categoria_pai_id', $this->categoriaId)->exists();
    }

    public function updatedAreaId(): void
    {
        $this->secaoId = '';
        $this->categoriaPaiId = '';
    }

    public function nova(?int $paiId = null): void
    {
        $this->reset('categoriaId', 'areaId', 'secaoId', 'categoriaPaiId', 'nome', 'descricao', 'ativo');
        $this->resetErrorBag();

        if ($paiId !== null) {
            $pai = Categoria::findOrFail($paiId);
            $this->areaId = (string) $pai->area_id;
            $this->secaoId = (string) ($pai->secao_id ?? '');
            $this->categoriaPaiId = (string) $pai->id;
        } elseif ($this->filtroArea !== '') {
            $this->areaId = $this->filtroArea;
        }

        Flux::modal('categoria')->show();
    }

    public function editar(int $id): void
    {
        $categoria = Categoria::findOrFail($id);

        $this->categoriaId = $categoria->id;
        $this->areaId = (string) $categoria->area_id;
        $this->secaoId = (string) ($categoria->secao_id ?? '');
        $this->categoriaPaiId = (string) ($categoria->categoria_pai_id ?? '');
        $this->nome = $categoria->nome;
        $this->descricao = (string) $categoria->descricao;
        $this->ativo = $categoria->ativo;
        $this->resetErrorBag();

        Flux::modal('categoria')->show();
    }

    public function salvar(): void
    {
        $dados = $this->validate([
            'areaId' => ['required', Rule::exists('areas', 'id')],
            'secaoId' => ['nullable', Rule::exists('secoes', 'id')->where('area_id', $this->areaId)],
            'categoriaPaiId' => ['nullable', Rule::exists('categorias', 'id')->where('area_id', $this->areaId)->whereNull('categoria_pai_id')],
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'ativo' => ['boolean'],
        ], [
            'secaoId.exists' => 'A seção precisa pertencer à área escolhida.',
            'categoriaPaiId.exists' => AdministracaoException::hierarquiaInvalida()->getMessage(),
        ], ['areaId' => 'área', 'secaoId' => 'seção', 'categoriaPaiId' => 'categoria pai']);

        $paiId = $dados['categoriaPaiId'] ? (int) $dados['categoriaPaiId'] : null;

        if ($paiId !== null && ($paiId === $this->categoriaId || $this->temSubcategorias)) {
            $this->addError('categoriaPaiId', 'Uma categoria que possui subcategorias não pode virar subcategoria.');

            return;
        }

        Categoria::updateOrCreate(['id' => $this->categoriaId], [
            'area_id' => (int) $dados['areaId'],
            'secao_id' => $dados['secaoId'] ? (int) $dados['secaoId'] : null,
            'categoria_pai_id' => $paiId,
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?: null,
            'ativo' => $dados['ativo'],
        ]);

        // Subcategorias acompanham a área da categoria principal.
        if ($this->categoriaId !== null) {
            Categoria::where('categoria_pai_id', $this->categoriaId)->update(['area_id' => (int) $dados['areaId']]);
        }

        Flux::modal('categoria')->close();
        Flux::toast(variant: 'success', text: $this->categoriaId ? 'Categoria atualizada.' : 'Categoria criada.');
        unset($this->categorias);
    }

    public function alternarAtivo(int $id): void
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->update(['ativo' => ! $categoria->ativo]);

        Flux::toast(variant: 'success', text: $categoria->ativo ? 'Categoria ativada.' : 'Categoria desativada. Ela deixa de aparecer na abertura de chamados.');
        unset($this->categorias);
    }

    public function excluir(int $id): void
    {
        $categoria = Categoria::findOrFail($id);

        if ($motivo = $categoria->motivoParaNaoExcluir()) {
            Flux::toast(variant: 'danger', text: AdministracaoException::emUso("a categoria \"{$categoria->nome}\"", $motivo)->getMessage());

            return;
        }

        $categoria->delete();

        Flux::toast(variant: 'success', text: 'Categoria excluída.');
        unset($this->categorias);
    }

    public function render(): View
    {
        return view('livewire.admin.categorias');
    }
}

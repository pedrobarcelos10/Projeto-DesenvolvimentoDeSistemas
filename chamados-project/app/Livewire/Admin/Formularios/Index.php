<?php

namespace App\Livewire\Admin\Formularios;

use App\Exceptions\AdministracaoException;
use App\Models\Formulario;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Formulários')]
class Index extends Component
{
    /** @return Collection<int, Formulario> */
    #[Computed]
    public function formularios(): Collection
    {
        return Formulario::query()
            ->with(['categoria.area', 'categoria.categoriaPai'])
            ->withCount(['campos', 'chamados'])
            ->orderByDesc('ativo')
            ->orderBy('nome')
            ->get();
    }

    public function alternarAtivo(int $id): void
    {
        $formulario = Formulario::findOrFail($id);
        $formulario->update(['ativo' => ! $formulario->ativo]);

        Flux::toast(variant: 'success', text: $formulario->ativo ? 'Formulário ativado.' : 'Formulário desativado. Ele deixa de aparecer na abertura de chamados.');
        unset($this->formularios);
    }

    public function excluir(int $id): void
    {
        $formulario = Formulario::findOrFail($id);

        if ($motivo = $formulario->motivoParaNaoExcluir()) {
            Flux::toast(variant: 'danger', text: AdministracaoException::emUso("o formulário \"{$formulario->nome}\"", $motivo)->getMessage());

            return;
        }

        $formulario->delete();

        Flux::toast(variant: 'success', text: 'Formulário excluído.');
        unset($this->formularios);
    }

    public function render(): View
    {
        return view('livewire.admin.formularios.index');
    }
}

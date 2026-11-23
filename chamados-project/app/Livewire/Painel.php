<?php

namespace App\Livewire;

use App\Enums\StatusChamado;
use App\Models\Chamado;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read User $usuario
 */
#[Title('Painel')]
class Painel extends Component
{
    #[Computed]
    public function usuario(): User
    {
        return Auth::user();
    }

    /**
     * Indicadores exibidos nos cartões do topo.
     *
     * @return list<array{rotulo: string, valor: int, icone: string, cor: string, link: string}>
     */
    #[Computed]
    public function indicadores(): array
    {
        $usuario = $this->usuario;
        $meus = fn () => Chamado::query()->where('requerente_id', $usuario->id);

        $indicadores = [
            [
                'rotulo' => 'Meus chamados em andamento',
                'valor' => $meus()->emAndamento()->count(),
                'icone' => 'inbox',
                'cor' => 'indigo',
                'link' => route('chamados.index', ['escopo' => 'meus']),
            ],
            [
                'rotulo' => 'Aguardando minha avaliação',
                'valor' => $meus()->where('status', StatusChamado::Solucionado)->count(),
                'icone' => 'check-badge',
                'cor' => 'emerald',
                'link' => route('chamados.index', ['escopo' => 'meus', 'status' => StatusChamado::Solucionado->value]),
            ],
        ];

        if ($usuario->fazAtendimento()) {
            $indicadores[] = [
                'rotulo' => 'Atribuídos a mim',
                'valor' => Chamado::query()->where('responsavel_id', $usuario->id)->emAndamento()->count(),
                'icone' => 'user',
                'cor' => 'violet',
                'link' => route('chamados.index', ['escopo' => 'atribuidos']),
            ];
            $indicadores[] = [
                'rotulo' => 'Fila sem responsável',
                'valor' => $this->filaQuery()->count(),
                'icone' => 'queue-list',
                'cor' => 'amber',
                'link' => route('chamados.index', ['escopo' => 'fila']),
            ];
        }

        return $indicadores;
    }

    /** @return Collection<int, Chamado> */
    #[Computed]
    public function meusRecentes(): Collection
    {
        return Chamado::query()
            ->where('requerente_id', $this->usuario->id)
            ->with(['responsavel', 'categoria.categoriaPai'])
            ->latest('criado_em')
            ->limit(5)
            ->get();
    }

    /** @return Collection<int, Chamado> */
    #[Computed]
    public function fila(): Collection
    {
        return $this->filaQuery()
            ->with(['requerente', 'responsavel', 'categoria.categoriaPai'])
            ->ordenadosPorPrioridade()
            ->limit(5)
            ->get();
    }

    /**
     * Chamados ativos da área do atendente, sem responsável, que não foram abertos por ele.
     *
     * @return Builder<Chamado>
     */
    private function filaQuery(): Builder
    {
        return Chamado::query()
            ->visiveisPara($this->usuario)
            ->emAndamento()
            ->whereNull('responsavel_id')
            ->where('requerente_id', '!=', $this->usuario->id);
    }

    public function render(): View
    {
        return view('livewire.painel');
    }
}

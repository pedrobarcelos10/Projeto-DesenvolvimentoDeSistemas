<?php

namespace App\Livewire\Chamados;

use App\Actions\Chamados\AdicionarAcompanhamento;
use App\Actions\Chamados\AlterarStatusChamado;
use App\Actions\Chamados\AtribuirChamado;
use App\Enums\PerfilUsuario;
use App\Enums\StatusChamado;
use App\Exceptions\ChamadoException;
use App\Models\Acompanhamento;
use App\Models\Chamado;
use App\Models\HistoricoChamado;
use App\Models\User;
use Closure;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read list<array{status: StatusChamado, rotulo: string, variante: string, cor: string|null, icone: string}> $acoesDeStatus
 */
class Exibir extends Component
{
    public Chamado $chamado;

    public string $conteudo = '';

    public bool $interno = false;

    public string $responsavelId = '';

    /** Status escolhido no botão, aguardando confirmação no modal. */
    public ?string $statusPretendido = null;

    public string $comentario = '';

    public function mount(Chamado $chamado): void
    {
        $this->authorize('visualizar', $chamado);

        $this->chamado = $chamado;
        $this->responsavelId = (string) ($chamado->responsavel_id ?? '');
    }

    public function render(): View
    {
        return view('livewire.chamados.exibir')->title($this->chamado->numero().' · '.$this->chamado->titulo);
    }

    /**
     * Acompanhamentos e histórico em uma única linha do tempo.
     * Notas internas só aparecem para quem atende o chamado.
     *
     * @return Collection<int, Acompanhamento|HistoricoChamado>
     */
    #[Computed]
    public function linhaDoTempo(): Collection
    {
        $podeAtender = Auth::user()->can('atender', $this->chamado);

        $acompanhamentos = $this->chamado->acompanhamentos()
            ->with('usuario')
            ->when(! $podeAtender, fn (Builder $q) => $q->where('interno', false))
            ->get();

        $historicos = $this->chamado->historicos()->with('usuario')->get();

        /** @var Collection<int, Acompanhamento|HistoricoChamado> $itens */
        $itens = collect([...$acompanhamentos, ...$historicos]);

        return $itens->sortBy(fn ($item) => [$item->criado_em->getTimestamp(), $item instanceof HistoricoChamado ? 0 : 1])->values();
    }

    /**
     * Atendentes da área do chamado, para o seletor de responsável.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function atendentes(): Collection
    {
        return User::query()
            ->where('ativo', true)
            ->where('perfil', PerfilUsuario::Atendente)
            ->whereHas('secao', fn (Builder $q) => $q->where('area_id', $this->chamado->area_id))
            ->orderBy('name')
            ->get();
    }

    /**
     * Botões de status disponíveis para o usuário atual.
     * Quem pode o quê está em ChamadoPolicy::alterarStatus().
     *
     * @return list<array{status: StatusChamado, rotulo: string, variante: string, cor: string|null, icone: string}>
     */
    #[Computed]
    public function acoesDeStatus(): array
    {
        $usuario = Auth::user();

        $opcoes = array_values(array_filter(
            $this->chamado->status->transicoesPermitidas(),
            fn (StatusChamado $status) => $usuario->can('alterarStatus', [$this->chamado, $status]),
        ));

        return array_map(fn (StatusChamado $status) => [
            'status' => $status,
            'rotulo' => $this->rotuloDaAcao($status),
            ...$this->estiloDaAcao($status),
        ], $opcoes);
    }

    /** @return array{variante: string, cor: string|null, icone: string} */
    private function estiloDaAcao(StatusChamado $status): array
    {
        $avaliando = $this->chamado->status === StatusChamado::Solucionado;

        return match (true) {
            $avaliando && $status === StatusChamado::Fechado => ['variante' => 'primary', 'cor' => 'emerald', 'icone' => 'hand-thumb-up'],
            $avaliando && $this->ehRequerente() => ['variante' => 'outline', 'cor' => 'amber', 'icone' => 'hand-thumb-down'],
            $status === StatusChamado::Solucionado => ['variante' => 'primary', 'cor' => 'emerald', 'icone' => 'check-circle'],
            $status === StatusChamado::EmAtendimento => ['variante' => 'filled', 'cor' => 'sky', 'icone' => 'play'],
            $status === StatusChamado::Pendente => ['variante' => 'filled', 'cor' => 'orange', 'icone' => 'pause'],
            $status === StatusChamado::Fechado => ['variante' => 'filled', 'cor' => 'indigo', 'icone' => 'lock-closed'],
            default => ['variante' => 'danger', 'cor' => null, 'icone' => 'x-circle'],
        };
    }

    public function ehRequerente(): bool
    {
        return $this->chamado->requerente_id === Auth::id();
    }

    public function acompanhar(AdicionarAcompanhamento $adicionar): void
    {
        $this->authorize('acompanhar', $this->chamado);

        $this->validate(['conteudo' => ['required', 'string', 'max:5000']]);

        $adicionar->executar($this->chamado, Auth::user(), $this->conteudo, $this->interno);

        $this->reset('conteudo', 'interno');
        unset($this->linhaDoTempo);

        Flux::toast(variant: 'success', text: 'Acompanhamento registrado.');
    }

    public function atribuir(AtribuirChamado $atribuir): void
    {
        $this->authorize('atender', $this->chamado);

        $responsavel = $this->responsavelId !== '' ? User::find($this->responsavelId) : null;

        $this->executar(fn () => $atribuir->executar($this->chamado, $responsavel, Auth::user()), 'Responsável atualizado.');
    }

    public function assumir(AtribuirChamado $atribuir): void
    {
        $this->authorize('atender', $this->chamado);

        $this->responsavelId = (string) Auth::id();

        $this->executar(fn () => $atribuir->executar($this->chamado, Auth::user(), Auth::user()), 'Você assumiu o chamado.');
    }

    public function prepararStatus(string $status): void
    {
        $this->statusPretendido = $status;
        $this->comentario = '';
        $this->resetErrorBag('comentario');

        Flux::modal('alterar-status')->show();
    }

    public function confirmarStatus(AlterarStatusChamado $alterar): void
    {
        $novo = StatusChamado::tryFrom((string) $this->statusPretendido);

        abort_unless($novo && $this->podeAlterarPara($novo), 403);

        $this->validate([
            'comentario' => [$this->comentarioObrigatorio($novo) ? 'required' : 'nullable', 'string', 'max:5000'],
        ]);

        $this->executar(
            fn () => $alterar->executar($this->chamado, $novo, Auth::user(), $this->comentario ?: null),
            "Chamado {$novo->rotulo()}.",
        );

        Flux::modal('alterar-status')->close();
        $this->reset('statusPretendido', 'comentario');
    }

    public function comentarioObrigatorio(?StatusChamado $status): bool
    {
        $atual = $this->chamado->status;

        // Solução, recusa/retomada e encerramento direto precisam de explicação.
        return $status === StatusChamado::Solucionado
            || ($status === StatusChamado::EmAtendimento && $atual === StatusChamado::Solucionado)
            || ($status === StatusChamado::Fechado && $atual !== StatusChamado::Solucionado);
    }

    public function rotuloDoComentario(StatusChamado $status): string
    {
        $avaliando = $this->chamado->status === StatusChamado::Solucionado;

        return match (true) {
            $status === StatusChamado::Solucionado => 'Qual foi a solução?',
            $avaliando && $status === StatusChamado::EmAtendimento && $this->ehRequerente() => 'Por que a solução não resolveu?',
            $avaliando && $status === StatusChamado::EmAtendimento => 'Por que o atendimento será retomado?',
            $status === StatusChamado::Fechado && ! $avaliando => 'Motivo do encerramento',
            default => 'Comentário',
        };
    }

    public function rotuloDaAcao(StatusChamado $status): string
    {
        $avaliando = $this->chamado->status === StatusChamado::Solucionado;

        return match (true) {
            $avaliando && $status === StatusChamado::Fechado => 'Aceitar solução',
            $avaliando && $status === StatusChamado::EmAtendimento => $this->ehRequerente() ? 'Recusar solução' : 'Retomar atendimento',
            default => $status->acao(),
        };
    }

    private function podeAlterarPara(StatusChamado $status): bool
    {
        return in_array($status, array_column($this->acoesDeStatus, 'status'), true);
    }

    private function executar(Closure $acao, string $mensagem): void
    {
        try {
            $acao();
        } catch (ChamadoException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->chamado->refresh();
        unset($this->linhaDoTempo, $this->acoesDeStatus);

        Flux::toast(variant: 'success', text: $mensagem);
    }
}

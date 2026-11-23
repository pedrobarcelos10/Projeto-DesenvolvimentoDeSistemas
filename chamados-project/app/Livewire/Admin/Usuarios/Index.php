<?php

namespace App\Livewire\Admin\Usuarios;

use App\Actions\Usuarios\SalvarUsuario;
use App\Enums\PerfilUsuario;
use App\Exceptions\AdministracaoException;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read User|null $emConfirmacao
 */
#[Title('Usuários')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $busca = '';

    #[Url(except: '')]
    public string $perfil = '';

    /** ativos | inativos | todos */
    #[Url(except: 'todos')]
    public string $situacao = 'todos';

    /** Usuário aguardando confirmação para ativar/desativar. */
    public ?int $usuarioEmConfirmacao = null;

    public function updated(string $propriedade): void
    {
        if (in_array($propriedade, ['busca', 'perfil', 'situacao'], true)) {
            $this->resetPage();
        }
    }

    /** @return LengthAwarePaginator<int, User> */
    #[Computed]
    public function usuarios(): LengthAwarePaginator
    {
        $termo = trim($this->busca);

        return User::query()
            ->with('secao.area')
            ->when($termo !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$termo}%")
                ->orWhere('email', 'like', "%{$termo}%")))
            ->when(PerfilUsuario::tryFrom($this->perfil), fn (Builder $q, PerfilUsuario $perfil) => $q->where('perfil', $perfil))
            ->when($this->situacao !== 'todos', fn (Builder $q) => $q->where('ativo', $this->situacao === 'ativos'))
            ->orderByDesc('ativo')
            ->orderBy('name')
            ->paginate(15);
    }

    /** @return array{total: int, ativos: int, atendentes: int, administradores: int} */
    #[Computed]
    public function resumo(): array
    {
        return [
            'total' => User::count(),
            'ativos' => User::where('ativo', true)->count(),
            'atendentes' => User::where('ativo', true)->where('perfil', PerfilUsuario::Atendente)->count(),
            'administradores' => User::where('ativo', true)->where('perfil', PerfilUsuario::Administrador)->count(),
        ];
    }

    #[Computed]
    public function emConfirmacao(): ?User
    {
        return $this->usuarioEmConfirmacao ? User::find($this->usuarioEmConfirmacao) : null;
    }

    public function confirmarAtivacao(int $usuarioId): void
    {
        $this->usuarioEmConfirmacao = $usuarioId;
        unset($this->emConfirmacao);

        Flux::modal('confirmar-ativacao')->show();
    }

    public function alternarAtivacao(SalvarUsuario $salvar): void
    {
        $usuario = $this->emConfirmacao;

        if ($usuario === null) {
            return;
        }

        try {
            $salvar->alterarAtivacao(Auth::user(), $usuario, ! $usuario->ativo);
        } catch (AdministracaoException $e) {
            Flux::modal('confirmar-ativacao')->close();
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        Flux::modal('confirmar-ativacao')->close();
        Flux::toast(variant: 'success', text: $usuario->ativo ? "{$usuario->name} foi ativado(a)." : "{$usuario->name} foi desativado(a).");

        $this->usuarioEmConfirmacao = null;
        unset($this->usuarios, $this->resumo, $this->emConfirmacao);
    }

    public function render(): View
    {
        return view('livewire.admin.usuarios.index');
    }
}

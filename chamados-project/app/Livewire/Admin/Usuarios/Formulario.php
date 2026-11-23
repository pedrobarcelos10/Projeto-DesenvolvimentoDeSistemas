<?php

namespace App\Livewire\Admin\Usuarios;

use App\Actions\Usuarios\SalvarUsuario;
use App\Exceptions\AdministracaoException;
use App\Models\Secao;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Criação e edição de usuários. A única forma de criar contas no sistema.
 *
 * @property-read User|null $usuario
 */
class Formulario extends Component
{
    #[Locked]
    public ?int $usuarioId = null;

    public string $name = '';

    public string $email = '';

    public string $perfil = 'requerente';

    public string $secaoId = '';

    public bool $ativo = true;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(?User $usuario = null): void
    {
        if ($usuario?->exists) {
            $this->usuarioId = $usuario->id;
            $this->name = $usuario->name;
            $this->email = $usuario->email;
            $this->perfil = $usuario->perfil->value;
            $this->secaoId = (string) ($usuario->secao_id ?? '');
            $this->ativo = $usuario->ativo;
        }
    }

    public function render(): View
    {
        return view('livewire.admin.usuarios.formulario')->title($this->editando() ? 'Editar usuário' : 'Novo usuário');
    }

    #[Computed]
    public function usuario(): ?User
    {
        return $this->usuarioId ? User::findOrFail($this->usuarioId) : null;
    }

    /**
     * Seções ativas agrupadas pelo nome da área.
     *
     * @return Collection<array-key, EloquentCollection<int, Secao>>
     */
    #[Computed]
    public function secoesPorArea(): Collection
    {
        return Secao::query()
            ->with('area')
            ->where(fn ($q) => $q->where('ativo', true)->when($this->secaoId !== '', fn ($q) => $q->orWhere('id', $this->secaoId)))
            ->get()
            ->sortBy(fn (Secao $secao) => $secao->area->nome.' '.$secao->nome)
            ->groupBy(fn (Secao $secao) => $secao->area->nome)
            ->toBase();
    }

    public function editando(): bool
    {
        return $this->usuarioId !== null;
    }

    public function editandoASiMesmo(): bool
    {
        return $this->usuarioId === Auth::id();
    }

    public function salvar(SalvarUsuario $salvar): void
    {
        $this->email = Str::lower(trim($this->email));

        $regras = $salvar->regras($this->usuario, $this->perfil);

        $validado = $this->validate([
            'name' => $regras['name'],
            'email' => $regras['email'],
            'perfil' => $regras['perfil'],
            'secaoId' => $regras['secao_id'],
            'ativo' => $regras['ativo'],
            'password' => $regras['password'],
        ], [
            'secaoId.required' => 'Atendentes precisam de uma lotação: ela define a área que atendem.',
        ], [
            'secaoId' => 'lotação',
            'name' => 'nome',
            'password' => 'senha',
        ]);

        try {
            $usuario = $salvar->executar(Auth::user(), [
                'name' => $validado['name'],
                'email' => $validado['email'],
                'perfil' => $validado['perfil'],
                'secao_id' => $validado['secaoId'] ?: null,
                'ativo' => $validado['ativo'],
                'password' => $validado['password'] ?: null,
            ], $this->usuario);
        } catch (AdministracaoException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: $this->editando() ? 'Usuário atualizado.' : "Conta de {$usuario->name} criada.");

        $this->redirectRoute('admin.usuarios.index', navigate: true);
    }
}

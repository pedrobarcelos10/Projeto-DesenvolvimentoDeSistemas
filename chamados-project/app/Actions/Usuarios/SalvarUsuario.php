<?php

namespace App\Actions\Usuarios;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\PerfilUsuario;
use App\Exceptions\AdministracaoException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Cria ou atualiza um usuário. Só o administrador chama esta action
 * (as telas ficam atrás do gate `administrar`); não existe cadastro público.
 *
 * Regras:
 * - atendente precisa de lotação (seção), pois ela define a área que atende;
 * - o administrador não pode desativar a si mesmo nem tirar o próprio perfil de admin;
 * - o sistema nunca fica sem um administrador ativo;
 * - contas criadas pelo administrador já nascem com o e-mail verificado.
 */
class SalvarUsuario
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Regras de validação dos dados do usuário. Na edição, a senha é opcional
     * (em branco mantém a atual).
     *
     * @return array<string, array<int, mixed>>
     */
    public function regras(?User $usuario, string $perfil): array
    {
        return [
            ...$this->profileRules($usuario?->id),
            'perfil' => ['required', Rule::enum(PerfilUsuario::class)],
            'secao_id' => [Rule::requiredIf($perfil === PerfilUsuario::Atendente->value), 'nullable', Rule::exists('secoes', 'id')],
            'ativo' => ['boolean'],
            'password' => $usuario?->exists
                ? ['nullable', 'string', Password::default(), 'confirmed']
                : $this->passwordRules(),
        ];
    }

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     perfil: PerfilUsuario|string,
     *     secao_id?: int|string|null,
     *     ativo?: bool,
     *     password?: string|null,
     * }  $dados
     */
    public function executar(User $autor, array $dados, ?User $usuario = null): User
    {
        $usuario ??= new User;

        $perfil = $dados['perfil'] instanceof PerfilUsuario ? $dados['perfil'] : PerfilUsuario::from($dados['perfil']);
        $secaoId = filled($dados['secao_id'] ?? null) ? (int) $dados['secao_id'] : null;
        $ativo = (bool) ($dados['ativo'] ?? true);

        if ($perfil === PerfilUsuario::Atendente && $secaoId === null) {
            throw AdministracaoException::atendenteSemLotacao();
        }

        if ($usuario->exists) {
            $this->protegerAcessoAdministrativo($autor, $usuario, $perfil, $ativo);
        }

        $usuario->fill([
            'name' => $dados['name'],
            'email' => Str::lower($dados['email']),
            'secao_id' => $secaoId,
        ]);

        $usuario->forceFill(['perfil' => $perfil, 'ativo' => $ativo]);

        if (filled($dados['password'] ?? null)) {
            $usuario->password = $dados['password'];
        }

        if (! $usuario->exists) {
            $usuario->forceFill(['email_verified_at' => now()]);
        }

        DB::transaction(fn () => $usuario->save());

        return $usuario;
    }

    /** Ativa ou desativa sem mexer nos demais dados. */
    public function alterarAtivacao(User $autor, User $usuario, bool $ativo): User
    {
        $this->protegerAcessoAdministrativo($autor, $usuario, $usuario->perfil, $ativo);

        $usuario->forceFill(['ativo' => $ativo])->save();

        return $usuario;
    }

    private function protegerAcessoAdministrativo(User $autor, User $usuario, PerfilUsuario $perfil, bool $ativo): void
    {
        $perdeAdministracao = $usuario->ehAdministrador() && $usuario->ativo
            && ($perfil !== PerfilUsuario::Administrador || ! $ativo);

        if (! $perdeAdministracao) {
            return;
        }

        if ($usuario->is($autor)) {
            throw AdministracaoException::naoPodeAlterarProprioAcesso();
        }

        $outrosAdministradores = User::query()
            ->where('perfil', PerfilUsuario::Administrador)
            ->where('ativo', true)
            ->whereKeyNot($usuario->id)
            ->exists();

        if (! $outrosAdministradores) {
            throw AdministracaoException::ultimoAdministrador();
        }
    }
}

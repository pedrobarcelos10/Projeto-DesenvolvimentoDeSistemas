<?php

namespace App\Models;

use App\Enums\PerfilUsuario;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Usuário do sistema.
 *
 * O model, a tabela `users` e as colunas name/email/password mantêm o padrão
 * do Laravel porque a autenticação (Fortify, passkeys, 2FA) depende deles.
 * As colunas do domínio são em português.
 *
 * `perfil` e `ativo` ficam fora do $fillable de propósito: só o
 * administrador altera esses campos, de forma explícita.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property PerfilUsuario $perfil
 * @property int|null $secao_id
 * @property bool $ativo
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Secao|null $secao
 */
#[Fillable(['name', 'email', 'password', 'secao_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /** @var array<string, mixed> */
    protected $attributes = [
        'perfil' => 'requerente',
        'ativo' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'perfil' => PerfilUsuario::class,
            'ativo' => 'boolean',
        ];
    }

    /** @return BelongsTo<Secao, $this> */
    public function secao(): BelongsTo
    {
        return $this->belongsTo(Secao::class);
    }

    /**
     * Chamados que o usuário abriu.
     *
     * @return HasMany<Chamado, $this>
     */
    public function chamadosAbertos(): HasMany
    {
        return $this->hasMany(Chamado::class, 'requerente_id');
    }

    /**
     * Chamados sob responsabilidade do usuário.
     *
     * @return HasMany<Chamado, $this>
     */
    public function chamadosAtribuidos(): HasMany
    {
        return $this->hasMany(Chamado::class, 'responsavel_id');
    }

    public function ehAdministrador(): bool
    {
        return $this->perfil === PerfilUsuario::Administrador;
    }

    public function ehAtendente(): bool
    {
        return $this->perfil === PerfilUsuario::Atendente;
    }

    /** Pode atender chamados de alguma área (atendente ou administrador). */
    public function fazAtendimento(): bool
    {
        return $this->ativo && ($this->ehAdministrador() || ($this->ehAtendente() && $this->areaAtendidaId() !== null));
    }

    /** Área cujos chamados o usuário atende (a da seção em que está lotado). */
    public function areaAtendidaId(): ?int
    {
        return $this->secao?->area_id;
    }

    /**
     * Pode atuar como atendente nos chamados desta área?
     * Administrador sempre pode; atendente só na área em que está lotado.
     */
    public function podeAtenderArea(int $areaId): bool
    {
        if (! $this->ativo) {
            return false;
        }

        if ($this->ehAdministrador()) {
            return true;
        }

        return $this->ehAtendente() && $this->areaAtendidaId() === $areaId;
    }

    public function podeAtender(Chamado $chamado): bool
    {
        return $this->podeAtenderArea($chamado->area_id);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}

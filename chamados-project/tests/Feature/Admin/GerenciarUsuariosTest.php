<?php

namespace Tests\Feature\Admin;

use App\Enums\PerfilUsuario;
use App\Livewire\Admin\Usuarios\Formulario as FormularioUsuario;
use App\Livewire\Admin\Usuarios\Index as ListarUsuarios;
use App\Models\Secao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class GerenciarUsuariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_nao_existe_cadastro_publico(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
    }

    public function test_somente_administrador_acessa_a_area_administrativa(): void
    {
        $rotas = ['admin.usuarios.index', 'admin.usuarios.novo', 'admin.estrutura', 'admin.categorias', 'admin.formularios.index', 'admin.formularios.novo'];

        $this->get(route('admin.usuarios.novo'))->assertRedirect(route('login'));

        $requerente = User::factory()->create();
        $atendente = User::factory()->atendente()->create();
        $admin = User::factory()->administrador()->create();

        foreach ($rotas as $rota) {
            $this->actingAs($requerente)->get(route($rota))->assertForbidden();
            $this->actingAs($atendente)->get(route($rota))->assertForbidden();
            $this->actingAs($admin)->get(route($rota))->assertOk();
        }
    }

    public function test_administrador_cria_usuario(): void
    {
        $admin = User::factory()->administrador()->create();
        $secao = Secao::factory()->create();

        Livewire::actingAs($admin)
            ->test(FormularioUsuario::class)
            ->set('name', 'Maria Atendente')
            ->set('email', 'Maria@Prisma.test')
            ->set('perfil', PerfilUsuario::Atendente->value)
            ->set('secaoId', (string) $secao->id)
            ->set('password', 'senha-inicial-123')
            ->set('password_confirmation', 'senha-inicial-123')
            ->call('salvar')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.usuarios.index'));

        $usuario = User::where('email', 'maria@prisma.test')->firstOrFail();

        $this->assertSame(PerfilUsuario::Atendente, $usuario->perfil);
        $this->assertSame($secao->id, $usuario->secao_id);
        $this->assertTrue($usuario->ativo);
        $this->assertNotNull($usuario->email_verified_at);
        $this->assertTrue(Hash::check('senha-inicial-123', $usuario->password));
    }

    public function test_atendente_precisa_de_lotacao(): void
    {
        Livewire::actingAs(User::factory()->administrador()->create())
            ->test(FormularioUsuario::class)
            ->set('name', 'Sem Lotação')
            ->set('email', 'sem@prisma.test')
            ->set('perfil', PerfilUsuario::Atendente->value)
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('salvar')
            ->assertHasErrors(['secaoId']);
    }

    public function test_administrador_altera_perfil_e_area_de_um_usuario(): void
    {
        $usuario = User::factory()->create();
        $secao = Secao::factory()->create();

        Livewire::actingAs(User::factory()->administrador()->create())
            ->test(FormularioUsuario::class, ['usuario' => $usuario])
            ->assertSet('email', $usuario->email)
            ->set('perfil', PerfilUsuario::Atendente->value)
            ->set('secaoId', (string) $secao->id)
            ->call('salvar')
            ->assertHasNoErrors();

        $usuario->refresh();
        $this->assertSame(PerfilUsuario::Atendente, $usuario->perfil);
        $this->assertSame($secao->area_id, $usuario->areaAtendidaId());
    }

    public function test_administrador_desativa_e_reativa_usuario(): void
    {
        $usuario = User::factory()->create();

        $tela = Livewire::actingAs(User::factory()->administrador()->create())
            ->test(ListarUsuarios::class)
            ->call('confirmarAtivacao', $usuario->id)
            ->call('alternarAtivacao');

        $this->assertFalse($usuario->refresh()->ativo);

        $tela->call('confirmarAtivacao', $usuario->id)->call('alternarAtivacao');

        $this->assertTrue($usuario->refresh()->ativo);
    }

    public function test_administrador_nao_pode_se_desativar(): void
    {
        $admin = User::factory()->administrador()->create();
        User::factory()->administrador()->create();

        Livewire::actingAs($admin)
            ->test(ListarUsuarios::class)
            ->call('confirmarAtivacao', $admin->id)
            ->call('alternarAtivacao');

        $this->assertTrue($admin->refresh()->ativo);
    }

    public function test_ultimo_administrador_ativo_nao_e_rebaixado(): void
    {
        $admin = User::factory()->administrador()->create();
        $alvo = User::factory()->administrador()->create();

        // Com dois admins ativos, um pode rebaixar o outro...
        Livewire::actingAs($admin)
            ->test(FormularioUsuario::class, ['usuario' => $alvo])
            ->set('perfil', PerfilUsuario::Requerente->value)
            ->call('salvar')
            ->assertHasNoErrors();

        $this->assertSame(PerfilUsuario::Requerente, $alvo->refresh()->perfil);

        // ...mas ninguém consegue tirar o perfil do único admin restante (nem ele mesmo).
        Livewire::actingAs($admin)
            ->test(FormularioUsuario::class, ['usuario' => $admin])
            ->set('perfil', PerfilUsuario::Requerente->value)
            ->call('salvar');

        $this->assertSame(PerfilUsuario::Administrador, $admin->refresh()->perfil);
    }

    public function test_usuario_desativado_nao_consegue_entrar(): void
    {
        $usuario = User::factory()->inativo()->create();

        $this->post(route('login.store'), ['email' => $usuario->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_desativado_durante_a_sessao_e_desconectado(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario)->get(route('painel'))->assertOk();

        $usuario->forceFill(['ativo' => false])->save();

        $this->get(route('painel'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}

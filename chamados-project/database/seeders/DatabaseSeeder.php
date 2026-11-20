<?php

namespace Database\Seeders;

use App\Actions\Chamados\AbrirChamado;
use App\Actions\Chamados\AdicionarAcompanhamento;
use App\Actions\Chamados\AlterarStatusChamado;
use App\Actions\Chamados\AtribuirChamado;
use App\Enums\PrioridadeChamado;
use App\Enums\StatusChamado;
use App\Enums\TipoChamado;
use App\Models\Categoria;
use App\Models\Secao;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Popula o banco para desenvolvimento.
     * Todos os usuários de exemplo usam a senha "password".
     */
    public function run(): void
    {
        $this->call([EstruturaOrganizacionalSeeder::class, CatalogoSeeder::class]);

        if (app()->isProduction()) {
            return;
        }

        $suporte = Secao::where('nome', 'Suporte ao Usuário')->firstOrFail();
        $infra = Secao::where('nome', 'Infraestrutura')->firstOrFail();
        $rh = Secao::where('nome', 'Gestão de Pessoas')->firstOrFail();

        User::factory()->administrador()->create([
            'name' => 'Administrador',
            'email' => 'admin@prisma.test',
        ]);

        $atendente = User::factory()->atendente($suporte)->create([
            'name' => 'Ana Atendente',
            'email' => 'atendente@prisma.test',
        ]);

        User::factory()->atendente($infra)->create([
            'name' => 'Igor Infraestrutura',
            'email' => 'infra@prisma.test',
        ]);

        $requerente = User::factory()->create([
            'name' => 'Rafael Requerente',
            'email' => 'requerente@prisma.test',
            'secao_id' => $rh->id,
        ]);

        $this->criarChamadosDeExemplo($requerente, $atendente);
    }

    private function criarChamadosDeExemplo(User $requerente, User $atendente): void
    {
        $abrir = app(AbrirChamado::class);
        $atribuir = app(AtribuirChamado::class);
        $alterarStatus = app(AlterarStatusChamado::class);
        $acompanhar = app(AdicionarAcompanhamento::class);

        $categoria = fn (string $nome) => Categoria::where('nome', $nome)->value('id');

        $abrir->executar($requerente, [
            'titulo' => 'Impressora do 2º andar não imprime',
            'descricao' => 'A impressora fica com a luz laranja piscando e nenhum documento sai.',
            'tipo' => TipoChamado::Incidente,
            'prioridade' => PrioridadeChamado::Alta,
            'categoria_id' => $categoria('Impressora'),
        ]);

        $chamado = $abrir->executar($requerente, [
            'titulo' => 'Instalar o LibreOffice',
            'descricao' => 'Preciso do LibreOffice para abrir as planilhas enviadas por outro órgão.',
            'categoria_id' => $categoria('Instalação'),
        ]);
        $atribuir->executar($chamado, $atendente, $atendente);
        $acompanhar->executar($chamado, $atendente, 'Verificando a licença e a versão homologada.', interno: true);

        $chamado = $abrir->executar($requerente, [
            'titulo' => 'Esqueci minha senha de rede',
            'descricao' => 'Não consigo acessar o computador desde segunda-feira.',
            'tipo' => TipoChamado::Incidente,
            'prioridade' => PrioridadeChamado::Urgente,
            'categoria_id' => $categoria('Redefinição de senha'),
        ]);
        $atribuir->executar($chamado, $atendente, $atendente);
        $alterarStatus->executar($chamado, StatusChamado::Solucionado, $atendente, 'Senha redefinida. A nova senha temporária foi entregue pessoalmente.');
    }
}

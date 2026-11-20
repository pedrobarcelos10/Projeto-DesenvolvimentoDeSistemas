<?php

namespace Database\Seeders;

use App\Enums\PrioridadeChamado;
use App\Enums\TipoCampo;
use App\Enums\TipoChamado;
use App\Models\Area;
use App\Models\Categoria;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial da TI: categorias (tipos de solicitação) e um formulário de exemplo.
 */
class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $ti = Area::where('sigla', 'TI')->firstOrFail();
        $secoes = $ti->secoes()->pluck('id', 'nome');

        $arvore = [
            'Hardware' => ['secao' => 'Suporte ao Usuário', 'subcategorias' => ['Computador', 'Impressora', 'Periféricos']],
            'Acessos' => ['secao' => 'Suporte ao Usuário', 'subcategorias' => ['Criação de conta', 'Redefinição de senha', 'Permissão em pasta de rede']],
            'Rede e Internet' => ['secao' => 'Infraestrutura', 'subcategorias' => ['Sem conexão', 'Lentidão', 'VPN']],
            'Software' => ['secao' => 'Suporte ao Usuário', 'subcategorias' => ['Instalação', 'Erro em aplicativo']],
            'Sistemas internos' => ['secao' => 'Sistemas', 'subcategorias' => ['Erro no sistema', 'Nova funcionalidade']],
        ];

        foreach ($arvore as $nomePai => $config) {
            $secaoId = $secoes[$config['secao']] ?? null;

            $pai = Categoria::updateOrCreate(
                ['area_id' => $ti->id, 'categoria_pai_id' => null, 'nome' => $nomePai],
                ['secao_id' => $secaoId],
            );

            foreach ($config['subcategorias'] as $nome) {
                Categoria::updateOrCreate(
                    ['area_id' => $ti->id, 'categoria_pai_id' => $pai->id, 'nome' => $nome],
                    ['secao_id' => $secaoId],
                );
            }
        }

        $this->criarFormularioNovoColaborador();
    }

    private function criarFormularioNovoColaborador(): void
    {
        $categoria = Categoria::where('nome', 'Criação de conta')->firstOrFail();

        $formulario = $categoria->formularios()->updateOrCreate(['nome' => 'Acesso para novo colaborador'], [
            'descricao' => 'Peça a criação de conta de rede e e-mail para quem está chegando.',
            'tipo_chamado' => TipoChamado::Requisicao,
            'prioridade_padrao' => PrioridadeChamado::Media,
        ]);

        $campos = [
            ['rotulo' => 'Nome completo do colaborador', 'chave' => 'nome_colaborador', 'tipo' => TipoCampo::Texto, 'obrigatorio' => true],
            ['rotulo' => 'Matrícula', 'chave' => 'matricula', 'tipo' => TipoCampo::Texto, 'obrigatorio' => true],
            ['rotulo' => 'Data de início', 'chave' => 'data_inicio', 'tipo' => TipoCampo::Data, 'obrigatorio' => true],
            ['rotulo' => 'Vínculo', 'chave' => 'vinculo', 'tipo' => TipoCampo::Lista, 'obrigatorio' => true,
                'opcoes' => ['Servidor', 'Terceirizado', 'Estagiário']],
            ['rotulo' => 'Acessos necessários', 'chave' => 'acessos', 'tipo' => TipoCampo::MultiplaEscolha,
                'opcoes' => ['E-mail', 'Rede', 'VPN', 'Sistema interno']],
            ['rotulo' => 'Observações', 'chave' => 'observacoes', 'tipo' => TipoCampo::TextoLongo],
        ];

        foreach ($campos as $ordem => $campo) {
            $formulario->campos()->updateOrCreate(['chave' => $campo['chave']], [...$campo, 'ordem' => $ordem]);
        }
    }
}

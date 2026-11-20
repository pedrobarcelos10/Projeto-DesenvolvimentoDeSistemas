<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

/**
 * Estrutura organizacional inicial: áreas e suas seções.
 * Só a TI recebe chamados por enquanto; as demais são áreas requerentes.
 */
class EstruturaOrganizacionalSeeder extends Seeder
{
    public function run(): void
    {
        $estrutura = [
            ['nome' => 'Tecnologia da Informação', 'sigla' => 'TI', 'recebe_chamados' => true, 'secoes' => [
                'Suporte ao Usuário' => 'Atendimento de primeiro nível: estações, impressoras, acessos.',
                'Infraestrutura' => 'Redes, servidores, telefonia e backup.',
                'Sistemas' => 'Desenvolvimento e sustentação dos sistemas internos.',
            ]],
            ['nome' => 'Recursos Humanos', 'sigla' => 'RH', 'recebe_chamados' => false, 'secoes' => [
                'Gestão de Pessoas' => null,
                'Folha de Pagamento' => null,
            ]],
            ['nome' => 'Administração', 'sigla' => 'ADM', 'recebe_chamados' => false, 'secoes' => [
                'Compras e Contratos' => null,
                'Patrimônio' => null,
            ]],
        ];

        foreach ($estrutura as $dados) {
            $area = Area::updateOrCreate(
                ['sigla' => $dados['sigla']],
                ['nome' => $dados['nome'], 'recebe_chamados' => $dados['recebe_chamados']],
            );

            foreach ($dados['secoes'] as $nome => $descricao) {
                $area->secoes()->updateOrCreate(['nome' => $nome], ['descricao' => $descricao]);
            }
        }
    }
}

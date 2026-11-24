# ADR-002 · Regras de negócio em Actions, fora das telas e das rotas

- **Status:** aceita
- **Data:** 03/11/2026 (registrada em 24/11/2026)
- **Ficha do Book:** 15 · **Âncoras:** Martin (2019), Arquitetura Limpa; Sommerville (2018), 6.3.1

## Contexto
No modelo de sala, o ADR-001 do OS_OFB colocou a regra de negócio em `servicos.py`, fora da rota. No Laravel com
Livewire, a tentação é escrever a regra dentro do componente da tela, o que a deixaria difícil de testar e de
reaproveitar em outras telas (por exemplo, no painel de indicadores do Incremento 4).

## Decisão
Cada regra de negócio fica em uma classe de ação em `app/Actions`, com um único método `executar()`:

| Action | Responsabilidade |
|---|---|
| `Chamados\AbrirChamado` | Cria o chamado com status Aberto, direciona para a área/seção da categoria e registra o histórico |
| `Chamados\AtribuirChamado` | Define o responsável (só quem atende a área) e move Aberto → Em atendimento |
| `Chamados\AlterarStatusChamado` | Aplica a máquina de estados; só o requerente aceita a solução |
| `Chamados\AdicionarAcompanhamento` | Registra acompanhamentos e notas internas |
| `Usuarios\SalvarUsuario` | Cria e edita usuários garantindo lotação e ao menos um administrador ativo |
| `Formularios\SalvarFormulario` | Salva formulários e campos mantendo as chaves estáveis |

Rotas (`routes/web.php`) só ligam a URL à tela. Telas (`app/Livewire`) só validam a entrada e chamam a Action.

## Alternativas consideradas
1. **Regra dentro dos componentes Livewire:** menos arquivos, porém regra duplicada entre telas e não testável sem a tela.
2. **Regra nos models ("models gordos"):** centraliza, mas mistura persistência com regra e cresce sem limite.

## Consequências
- A regra é testada sem depender da interface (ex.: `AceiteSolucaoTest::test_a_action_recusa_aceite_feito_por_quem_nao_abriu_o_chamado`).
- O painel de indicadores e o relatório do Incremento 4 reutilizam as mesmas regras e escopos, sem duplicá-los.
- Violações de regra viram `ChamadoException`/`AdministracaoException`, com mensagem exibida ao usuário.

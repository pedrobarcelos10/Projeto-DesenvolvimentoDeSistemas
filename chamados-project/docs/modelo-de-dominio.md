# Prisma: modelo de domínio

Referência rápida das entidades, regras e telas. O código é a fonte da verdade:
`app/Enums`, `app/Models`, `app/Actions/Chamados`, `app/Policies/ChamadoPolicy.php` e `resources/views/pages`.

## Convenção de nomes

Tudo do domínio é em português: models, tabelas, colunas, enums, actions, policies, rotas e telas.
Os timestamps dos models do domínio são `criado_em`, `atualizado_em` e `excluido_em` (ver `App\Models\ModeloBase`).

**Exceção:** o model `User`, a tabela `users` e as colunas `name`, `email` e `password` seguem o padrão do
Laravel, porque a autenticação (Fortify, passkeys, 2FA, sessões) depende desses nomes. As colunas que o
sistema acrescenta em `users` são em português: `perfil`, `secao_id`, `ativo`.

| Conceito | Model | Tabela |
|---|---|---|
| Área | `Area` | `areas` |
| Seção | `Secao` | `secoes` |
| Usuário | `User` | `users` |
| Categoria (tipo de solicitação) | `Categoria` | `categorias` |
| Formulário | `Formulario` | `formularios` |
| Campo de formulário | `CampoFormulario` | `campos_formulario` |
| Chamado | `Chamado` | `chamados` |
| Acompanhamento | `Acompanhamento` | `acompanhamentos` |
| Histórico do chamado | `HistoricoChamado` | `historicos_chamado` |

## Estrutura organizacional

```
Área (TI, RH, ADM)  ── recebe_chamados: só áreas que atendem aparecem como destino
 └── Seção (Suporte, Infraestrutura, Sistemas)
      └── Usuário (lotação: users.secao_id)
```

## Perfis (`users.perfil`)

| Perfil | Pode |
|---|---|
| Requerente | Abrir chamados, ver e acompanhar os próprios, cancelar enquanto em andamento, aceitar/recusar solução |
| Atendente | Tudo do requerente + atender os chamados da **área da sua seção** (status, responsável, notas internas) |
| Administrador | Ver e atender tudo + área administrativa (gate `administrar`): estrutura, catálogo, formulários, usuários |

Um usuário inativo (`ativo = false`) perde todos os acessos.

## Catálogo

- **Categoria**: pertence a uma área, pode ter seção padrão e categoria pai (`Hardware > Impressora`).
- **Formulário**: montado pelo administrador, ligado a uma categoria; define tipo e prioridade padrão.
- **Campo de formulário** (`TipoCampo`): texto, texto longo, número, e-mail, data, lista, escolha única, múltipla escolha.
  `CampoFormulario::regrasValidacao()` gera as regras de validação.

## Chamado

### Fluxo de status (`StatusChamado`)

```
Aberto ──► Em atendimento ◄──► Pendente
  │              │                 │
  └──────────────┴──► Solucionado ◄┘
                          │
              ┌───────────┴───────────┐
              ▼                       ▼
           Fechado            Em atendimento (solução recusada)

Qualquer status em andamento ──► Cancelado
Fechado e Cancelado são finais.
```

### Regras (`app/Actions/Chamados`)

| Action | Regra |
|---|---|
| `AbrirChamado` | Nasce "Aberto"; destino vem da categoria (ou área informada); área precisa receber chamados; respostas do formulário são copiadas |
| `AtribuirChamado` | Responsável precisa atender a área; atribuir um chamado "Aberto" o move para "Em atendimento" |
| `AlterarStatusChamado` | Respeita o fluxo acima; preenche `solucionado_em`/`fechado_em`; reabrir limpa `solucionado_em` |
| `AdicionarAcompanhamento` | Nota interna só vale para quem atende a área |

Toda mudança relevante gera um registro em `historicos_chamado` (base da linha do tempo e dos indicadores).

### Permissões (`ChamadoPolicy`)

`listar`, `visualizar`, `abrir`, `editar`, `atender`, `acompanhar`, `cancelar`, `avaliarSolucao`, `excluir`.
Uso: `$usuario->can('atender', $chamado)` ou `@can('cancelar', $chamado)`.

## Telas

| Rota | Componente | O que faz |
|---|---|---|
| `/painel` | `pages::painel` | Indicadores, fila da área (atendentes) e chamados recentes |
| `/chamados` | `pages::chamados.index` | Listagem com busca e filtros (escopo, status, prioridade) |
| `/chamados/novo` | `pages::chamados.novo` | Catálogo de formulários + abertura genérica |
| `/chamados/{id}` | `pages::chamados.exibir` | Detalhes, linha do tempo, acompanhamentos, responsável e ações de status |

Componentes Blade: `<x-chamado.status>`, `<x-chamado.prioridade>`, `<x-chamado.tabela>`.

## Usuários de desenvolvimento (`php artisan migrate:fresh --seed`)

Senha de todos: `password`

| E-mail | Perfil |
|---|---|
| admin@prisma.test | Administrador |
| atendente@prisma.test | Atendente (TI > Suporte ao Usuário) |
| infra@prisma.test | Atendente (TI > Infraestrutura) |
| requerente@prisma.test | Requerente (RH > Gestão de Pessoas) |

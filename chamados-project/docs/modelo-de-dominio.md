# Prisma: modelo de domínio

Referência rápida das entidades, regras e telas. O código é a fonte da verdade:
`app/Enums`, `app/Models`, `app/Actions`, `app/Policies/ChamadoPolicy.php`, `app/Livewire` e `resources/views/livewire`.

## Convenção de nomes

Tudo do domínio é em português: models, tabelas, colunas, enums, actions, policies, rotas e telas.
Os timestamps dos models do domínio são `criado_em`, `atualizado_em` e `excluido_em` (ver `App\Models\ModeloBase`).

**Exceção:** o model `User`, a tabela `users` e as colunas `name`, `email` e `password` seguem o padrão do
Laravel, porque a autenticação (Fortify, sessões) depende desses nomes. As colunas que o
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
| Atendente | Tudo do requerente + atender os chamados da **área da sua seção** (status, responsável, notas internas, encerrar direto) |
| Administrador | Ver e atender tudo + área administrativa (gate `administrar`): usuários, áreas e seções, categorias, formulários |

Um usuário inativo (`ativo = false`) não consegue entrar, e quem for desativado durante a sessão é
desconectado na próxima requisição (`App\Http\Middleware\GarantirUsuarioAtivo`).

### Contas

Não existe cadastro público, 2FA nem passkeys: o login é por e-mail e senha (Fortify) e **só o administrador
cria contas**, em `/admin/usuarios/novo` (`App\Actions\Usuarios\SalvarUsuario`). Regras:

- atendente precisa de lotação (seção), que define a área que ele atende;
- o administrador não pode desativar a si mesmo nem tirar o próprio perfil de administrador;
- o sistema nunca fica sem um administrador ativo;
- contas criadas pelo administrador já nascem com o e-mail verificado.

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
  Fechado (solução aceita —      Em atendimento (solução recusada pelo
  somente o requerente)          requerente ou retomada pela equipe)

Qualquer status em andamento ──► Fechado (encerramento direto pela equipe, com motivo)
Qualquer status em andamento ──► Cancelado
Fechado e Cancelado são finais.
```

### Regras (`app/Actions/Chamados`)

| Action | Regra |
|---|---|
| `AbrirChamado` | Nasce "Aberto"; destino vem da categoria (ou área informada); área precisa receber chamados; respostas do formulário são copiadas |
| `AtribuirChamado` | Responsável precisa atender a área; atribuir um chamado "Aberto" o move para "Em atendimento" |
| `AlterarStatusChamado` | Respeita o fluxo acima; só o requerente aceita a solução (Solucionado → Fechado); preenche `solucionado_em`/`fechado_em`; reabrir limpa `solucionado_em` |
| `AdicionarAcompanhamento` | Nota interna só vale para quem atende a área |

Toda mudança relevante gera um registro em `historicos_chamado` (base da linha do tempo e dos indicadores).

### Permissões (`ChamadoPolicy`)

`listar`, `visualizar`, `abrir`, `editar`, `atender`, `acompanhar`, `cancelar`, `avaliarSolucao`, `alterarStatus`, `excluir`.
Uso: `$usuario->can('atender', $chamado)`, `@can('cancelar', $chamado)` ou
`$usuario->can('alterarStatus', [$chamado, StatusChamado::Fechado])`.

`alterarStatus` concentra quem pode mover o chamado para cada status:

| De → Para | Quem pode |
|---|---|
| Solucionado → Fechado (aceitar solução) | Somente quem abriu o chamado, mesmo que seja atendente ou administrador |
| Solucionado → Em atendimento | O requerente (recusa) ou a equipe (retoma o atendimento) |
| Em andamento → Cancelado | O requerente ou a equipe |
| Em andamento → qualquer outro, inclusive Fechado | A equipe da área (atendente) ou o administrador |

## Telas

Cada tela é um componente Livewire com a classe em `app/Livewire/<Caminho>.php` e a view em
`resources/views/livewire/<caminho>.blade.php` (ex.: `App\Livewire\Chamados\Exibir` →
`resources/views/livewire/chamados/exibir.blade.php`). `php artisan make:livewire` já gera nesse formato
(`config/livewire.php`, `make_command.type = class`).

| Rota | Componente (`App\Livewire\…`) | O que faz |
|---|---|---|
| `/painel` | `Painel` | Indicadores, fila da área (atendentes) e chamados recentes |
| `/chamados` | `Chamados\Index` | Listagem com busca e filtros (escopo, status, prioridade) |
| `/chamados/novo` | `Chamados\Novo` | Catálogo de formulários + abertura genérica |
| `/chamados/{id}` | `Chamados\Exibir` | Detalhes, linha do tempo, acompanhamentos, responsável e ações de status |

| `/admin/usuarios` | `Admin\Usuarios\Index` | Lista, filtros, ativar/desativar usuários |
| `/admin/usuarios/novo`, `/admin/usuarios/{id}` | `Admin\Usuarios\Formulario` | Criar/editar usuário: dados, senha, perfil, lotação, situação |
| `/admin/estrutura` | `Admin\Estrutura` | Áreas e seções |
| `/admin/categorias` | `Admin\Categorias` | Categorias e subcategorias do catálogo |
| `/admin/formularios`, `/admin/formularios/{id}` | `Admin\Formularios\{Index,Editar}` | Formulários e seus campos (`App\Actions\Formularios\SalvarFormulario`) |

As telas `/admin/*` exigem o gate `administrar`. Itens em uso (com chamados, usuários, seções etc.) não são
excluídos, apenas desativados (`motivoParaNaoExcluir()` nos models).

Componentes Blade: `<x-chamado.status>`, `<x-chamado.prioridade>`, `<x-chamado.tabela>`, `<x-indicador>`,
`<x-vazio>`, `<x-icone-secao>`, `<x-admin.cabecalho>`.

## Identidade visual

Marca: um prisma que recebe um feixe (o chamado) e o decompõe em cores (as áreas que atendem). Ícone em
`resources/views/components/app-logo-icon.blade.php`, `public/favicon.svg`, `public/favicon.ico` e
`public/apple-touch-icon.png`. Cor de ação: azul (`--color-accent` em `resources/css/app.css`), reservada à ação principal de cada tela;
status, prioridades e perfis usam cores semânticas nos badges.

## Usuários de desenvolvimento (`php artisan migrate:fresh --seed`)

Senha de todos: `password`

| E-mail | Perfil |
|---|---|
| admin@prisma.test | Administrador |
| atendente@prisma.test | Atendente (TI > Suporte ao Usuário) |
| infra@prisma.test | Atendente (TI > Infraestrutura) |
| requerente@prisma.test | Requerente (RH > Gestão de Pessoas) |

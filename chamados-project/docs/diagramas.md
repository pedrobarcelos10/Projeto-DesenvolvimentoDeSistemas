# Prisma · Diagramas (Fichas 14 e 15)

Os diagramas estão em Mermaid: o GitHub os desenha automaticamente ao abrir este arquivo.
Fonte da verdade: `database/migrations`, `app/Enums/StatusChamado.php` e `app/Policies/ChamadoPolicy.php`.

## 1. Modelo de dados (Ficha 14)

```mermaid
erDiagram
    AREAS ||--o{ SECOES : "possui"
    SECOES ||--o{ USERS : "lota"
    AREAS ||--o{ CATEGORIAS : "atende"
    SECOES |o--o{ CATEGORIAS : "seção padrão"
    CATEGORIAS |o--o{ CATEGORIAS : "subcategoria de"
    CATEGORIAS ||--o{ FORMULARIOS : "possui"
    FORMULARIOS ||--o{ CAMPOS_FORMULARIO : "possui"
    USERS ||--o{ CHAMADOS : "abre (requerente)"
    USERS |o--o{ CHAMADOS : "atende (responsável)"
    AREAS ||--o{ CHAMADOS : "recebe"
    SECOES |o--o{ CHAMADOS : "recebe"
    CATEGORIAS |o--o{ CHAMADOS : "classifica"
    FORMULARIOS |o--o{ CHAMADOS : "origina"
    CHAMADOS ||--o{ ACOMPANHAMENTOS : "tem"
    CHAMADOS ||--o{ HISTORICOS_CHAMADO : "registra"
    USERS |o--o{ ACOMPANHAMENTOS : "escreve"
    USERS |o--o{ HISTORICOS_CHAMADO : "executa"

    AREAS {
        bigint id PK
        string nome
        string sigla UK
        boolean recebe_chamados
        boolean ativo
    }
    SECOES {
        bigint id PK
        bigint area_id FK
        string nome
        boolean ativo
    }
    USERS {
        bigint id PK
        string name
        string email UK
        string password
        string perfil "requerente | atendente | administrador"
        bigint secao_id FK "lotação"
        boolean ativo
    }
    CATEGORIAS {
        bigint id PK
        bigint area_id FK
        bigint secao_id FK
        bigint categoria_pai_id FK
        string nome
        boolean ativo
    }
    FORMULARIOS {
        bigint id PK
        bigint categoria_id FK
        string nome
        string tipo_chamado
        string prioridade_padrao
        boolean ativo
    }
    CAMPOS_FORMULARIO {
        bigint id PK
        bigint formulario_id FK
        string rotulo
        string chave
        string tipo
        json opcoes
        boolean obrigatorio
        int ordem
    }
    CHAMADOS {
        bigint id PK
        string titulo
        text descricao
        string tipo "incidente | requisicao"
        string status
        string prioridade "baixa | media | alta | urgente"
        bigint requerente_id FK
        bigint responsavel_id FK
        bigint area_id FK
        bigint secao_id FK
        bigint categoria_id FK
        bigint formulario_id FK
        json respostas_formulario
        timestamp solucionado_em
        timestamp fechado_em
        timestamp criado_em
    }
    ACOMPANHAMENTOS {
        bigint id PK
        bigint chamado_id FK
        bigint usuario_id FK
        text conteudo
        boolean interno "nota visível só à equipe"
    }
    HISTORICOS_CHAMADO {
        bigint id PK
        bigint chamado_id FK
        bigint usuario_id FK
        string evento
        string valor_anterior
        string valor_novo
        timestamp criado_em
    }
```

**Regras de integridade principais**
- Área, seção, categoria ou formulário em uso não são excluídos, apenas desativados (`motivoParaNaoExcluir()`).
- O chamado guarda uma cópia das respostas do formulário (`respostas_formulario`), preservada mesmo se o formulário mudar.
- O histórico (`historicos_chamado`) só recebe inserções: nunca é editado.

## 2. Máquina de estados do chamado (Ficha 14)

```mermaid
stateDiagram-v2
    [*] --> Aberto : requerente abre
    Aberto --> EmAtendimento : atendente assume / altera
    Aberto --> Pendente : equipe
    Aberto --> Solucionado : equipe informa a solução
    EmAtendimento --> Pendente : equipe
    Pendente --> EmAtendimento : equipe
    EmAtendimento --> Solucionado : equipe informa a solução
    Pendente --> Solucionado : equipe informa a solução
    Solucionado --> Fechado : só o requerente aceita
    Solucionado --> EmAtendimento : requerente recusa (com motivo) ou equipe retoma
    Aberto --> Fechado : equipe encerra (com motivo)
    EmAtendimento --> Fechado : equipe encerra (com motivo)
    Pendente --> Fechado : equipe encerra (com motivo)
    Aberto --> Cancelado : requerente ou equipe
    EmAtendimento --> Cancelado : requerente ou equipe
    Pendente --> Cancelado : requerente ou equipe
    Fechado --> [*]
    Cancelado --> [*]

    EmAtendimento : Em atendimento
```

Cada transição grava uma linha em `historicos_chamado`. Atribuir um responsável a um chamado Aberto
o move automaticamente para Em atendimento (`AtribuirChamado`).

## 3. Arquitetura em camadas (Ficha 15)

```mermaid
flowchart TB
    U([Navegador do usuário]) --> R

    subgraph Apresentacao["Apresentação"]
        R["Rotas · routes/web.php<br/>(só ligam URL à tela)"]
        T["Telas Livewire · app/Livewire<br/>views Blade + Flux · resources/views/livewire<br/>(validam a entrada e exibem o resultado)"]
    end

    subgraph Seguranca["Controle de acesso"]
        P["ChamadoPolicy · gate administrar<br/>middleware GarantirUsuarioAtivo"]
    end

    subgraph Aplicacao["Regras de negócio"]
        A["Actions · app/Actions<br/>AbrirChamado · AtribuirChamado<br/>AlterarStatusChamado · AdicionarAcompanhamento<br/>SalvarUsuario · SalvarFormulario"]
    end

    subgraph Dominio["Domínio e dados"]
        M["Models Eloquent · app/Models<br/>Enums · app/Enums (StatusChamado, PerfilUsuario…)"]
    end

    DB[("MySQL · banco prisma<br/>migrations em database/migrations")]

    R --> T
    T -- "can(...)" --> P
    T --> A
    A --> M
    M --> DB
```

Dependências apontam sempre para dentro: telas conhecem Actions, Actions conhecem Models, Models conhecem o banco.
Nenhuma regra de negócio fica nas rotas ou nas telas (ADR-002). Decisões detalhadas em `docs/adr/`.

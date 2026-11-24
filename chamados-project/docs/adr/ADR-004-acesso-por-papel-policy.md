# ADR-004 · Acesso por papel centralizado em Policy, com atendimento por área

- **Status:** aceita
- **Data:** 17/11/2026 (registrada em 24/11/2026)
- **Ficha do Book:** 15 e 17 · **Âncoras:** OWASP Top 10:2025 (A01 · Broken Access Control); Sommerville (2018), 8.2

## Contexto
O Incremento 3 introduz três papéis: **requerente** (abre e acompanha os próprios chamados), **atendente**
(atende os chamados da área em que está lotado) e **administrador** (controla todo o sistema). Uma checagem
esquecida em uma única tela permitiria ver ou alterar chamados de outra pessoa.

## Decisão
- O perfil fica em `users.perfil`; a área atendida vem da seção de lotação (`users.secao_id`).
- Todas as permissões sobre chamados ficam em `app/Policies/ChamadoPolicy.php` (`visualizar`, `atender`,
  `cancelar`, `avaliarSolucao`, `alterarStatus` …). As telas só perguntam `$usuario->can(...)`.
- A área administrativa exige o gate `administrar`; o middleware `GarantirUsuarioAtivo` desconecta usuários desativados.
- A listagem usa o escopo `Chamado::visiveisPara($usuario)`, então nunca traz chamados de outras áreas.

## Alternativas consideradas
1. **Checagens espalhadas em cada tela:** mais rápido de escrever, mas fácil de esquecer em uma tela nova.
2. **Pacote de papéis e permissões (ex.: spatie/laravel-permission):** flexível, porém excessivo para três papéis fixos.

## Consequências
- Uma única regra por ação, testada automaticamente (`GerenciarUsuariosTest`, `AceiteSolucaoTest`).
- Tentativas de acesso indevido retornam **403 Proibido** (ex.: requerente em `/admin/usuarios`).
- Um novo papel exige alterar a Policy e o enum `PerfilUsuario`, o que é aceitável no escopo do MVP.

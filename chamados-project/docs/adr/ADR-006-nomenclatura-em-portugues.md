# ADR-006 · Nomenclatura do domínio em português

- **Status:** aceita
- **Data:** 03/11/2026 (registrada em 24/11/2026)
- **Ficha do Book:** 15 · **Âncora:** Sommerville (2018), cap. 4 (linguagem do domínio nos requisitos)

## Contexto
Os usuários (diretor de TI, atendentes, requerentes) falam "chamado", "seção", "acompanhamento". Misturar termos em
inglês no código e em português na interface dificulta conversar sobre regras e ler o Book junto com o código.

## Decisão
Models, tabelas, colunas, enums, actions, permissões, rotas, componentes e nomes de testes ficam em português
(`Chamado`, `secoes`, `AlterarStatusChamado`, `atender`). Timestamps do domínio: `criado_em`, `atualizado_em`,
`excluido_em` (classe `ModeloBase`).

**Exceção:** o model `User`, a tabela `users` e as colunas `name`, `email` e `password` seguem o padrão do Laravel,
porque a autenticação (Fortify e sessões) depende desses nomes.

## Alternativas consideradas
1. **Tudo em inglês:** padrão de mercado, mas distante da linguagem dos usuários e do Book.
2. **Português inclusive em `users`:** exigiria reescrever partes da autenticação, com risco de falhas de segurança.

## Consequências
- O Book, a interface e o código usam as mesmas palavras.
- Nomes que o Laravel pluralizaria errado declaram a tabela explicitamente (`#[Table('secoes')]`).

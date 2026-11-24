# ADR-003 · MySQL como banco da aplicação; SQLite em memória nos testes

- **Status:** aceita
- **Data:** 03/11/2026 (registrada em 24/11/2026)
- **Ficha do Book:** 14 e 15 · **Âncora:** Sommerville (2018), cap. 6

## Contexto
O starter kit do Laravel vem configurado com SQLite. O sistema terá vários atendentes gravando ao mesmo tempo e
será publicado futuramente em um órgão público, onde normalmente já existe um servidor de banco gerenciado.

## Decisão
Usar **MySQL 8** (banco `prisma`, `utf8mb4`) no desenvolvimento e na implantação. Os testes automatizados usam
**SQLite em memória** (`phpunit.xml`), recriado a cada teste.

## Alternativas consideradas
1. **SQLite em arquivo:** zero configuração, mas bloqueia o banco inteiro a cada escrita e é pouco comum em
   ambientes de produção corporativos.
2. **PostgreSQL:** equivalente tecnicamente, porém o ambiente local já tinha MySQL e MySQL Workbench.

## Consequências
- Quem for reproduzir o projeto precisa de um servidor MySQL (passo documentado no README / Ficha 19).
- As migrations são escritas sem recursos exclusivos de um banco, então rodam nos dois (MySQL e SQLite).
- A suíte de testes roda rápido e não suja o banco de desenvolvimento.
- Os dados persistem após reiniciar a aplicação (critério da Definição de Pronto).

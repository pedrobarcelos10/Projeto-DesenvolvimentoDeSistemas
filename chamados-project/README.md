# Prisma · Sistema de chamados

Projeto Integrador de Desenvolvimento de Sistemas (CEUB · 2026/2 · Prof. Uender Amaral).
Aluno: Pedro Vinícius de Paula Barcelos.

**Problema:** o diretor da área de TI de um órgão público federal não tem um registro consolidado das
solicitações, o que dificulta acompanhar as demandas e produzir indicadores de eficiência.
**Solução:** um sistema web para abrir, atender e acompanhar chamados por área, com histórico completo.

| Documento | Onde |
|---|---|
| Caderno de dinâmicas (Fichas 1–10) | `docs/Ideia_Ao_Projeto.pdf` |
| Book · Kit de Artefatos (Fichas 11–20) | `docs/Book.xlsx` |
| Modelo de domínio e regras | `docs/modelo-de-dominio.md` |
| Diagramas (dados, estados, camadas) | `docs/diagramas.md` |
| Decisões de arquitetura (ADRs) | `docs/adr/` |
| Evidências de testes e segurança | `docs/evidencias/` |

## Tecnologias
Laravel 13 (PHP 8.6), Livewire 4, Flux, Tailwind CSS 4, MySQL 8 e PHPUnit. A escolha está justificada no
`docs/adr/ADR-001-stack-laravel-livewire-flux.md`.

## Guia de reprodução (Ficha 19)

### Pré-requisitos
| Ferramenta | Versão mínima | Como conferir |
|---|---|---|
| PHP (com extensões `pdo_mysql`, `mbstring`, `openssl`, `zip`) | 8.6 | `php --version` |
| Composer | 2.x | `composer --version` |
| Node.js e npm | 20 | `node --version` |
| MySQL Server (e, se quiser, MySQL Workbench) | 8.0 | `mysql --version` |
| Git | qualquer | `git --version` |

### Primeira execução
| Nº | Etapa | Comando | Finalidade |
|---|---|---|---|
| 1 | Clonar o repositório | `git clone https://github.com/pedrobarcelos10/Projeto-DesenvolvimentoDeSistemas.git` | Baixar o código e o Book |
| 2 | Entrar na pasta da aplicação | `cd Projeto-DesenvolvimentoDeSistemas/chamados-project` | Todos os comandos seguintes rodam aqui |
| 3 | Instalar dependências PHP | `composer install` | Laravel, Livewire, Flux e PHPUnit |
| 4 | Instalar dependências JavaScript | `npm install` | Vite e Tailwind |
| 5 | Criar o arquivo de configuração | `copy .env.example .env` (Windows) ou `cp .env.example .env` | Copiar as configurações padrão |
| 6 | Gerar a chave da aplicação | `php artisan key:generate` | Chave usada para criptografar sessões |
| 7 | Criar o banco | No MySQL Workbench: `CREATE DATABASE prisma CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;` | Banco vazio da aplicação |
| 8 | Ajustar o acesso ao banco | Editar `DB_USERNAME` e `DB_PASSWORD` no `.env` | Usuário e senha do seu MySQL (padrão: `root`, sem senha) |
| 9 | Criar as tabelas e os dados de exemplo | `php artisan migrate --seed` | Estrutura, catálogo da TI, usuários e chamados de exemplo |
| 10 | Rodar os testes | `php artisan test` | Resultado esperado: **46 passed** |
| 11 | Compilar o front-end | `npm run build` | Gera CSS e JS em `public/build` |
| 12 | Iniciar o sistema | `php artisan serve` | Servidor em http://127.0.0.1:8000 |
| 13 | Acessar no navegador | http://127.0.0.1:8000 | Entrar com um dos usuários abaixo |

### Próximas execuções
```bash
composer run dev
```
Sobe o servidor e o Vite juntos (recarrega o CSS a cada alteração). Alternativa: `php artisan serve` em um
terminal e `npm run dev` em outro.

### Usuários de exemplo (criados pelo passo 9; senha de todos: `password`)
| E-mail | Perfil | O que testar |
|---|---|---|
| `requerente@prisma.test` | Requerente (RH) | Abrir chamado, acompanhar, aceitar ou recusar a solução |
| `atendente@prisma.test` | Atendente (TI · Suporte) | Fila da área, assumir, mudar status, notas internas |
| `infra@prisma.test` | Atendente (TI · Infraestrutura) | Mesma área (TI), outra seção |
| `admin@prisma.test` | Administrador | Usuários, áreas e seções, categorias e formulários |

### Fluxo mínimo para conferir (critério de aceitação do Incremento 1)
1. Entre como `requerente@prisma.test` e abra um chamado em **Abrir chamado**.
2. Confira que ele aparece em **Chamados** com status **Aberto**.
3. Pare o servidor (`Ctrl+C`), suba de novo e confira que o chamado continua lá (dado persistido no MySQL).

### Problemas comuns
| Sintoma | Causa provável | Solução |
|---|---|---|
| `SQLSTATE[HY000] [1049] Unknown database 'prisma'` | Banco não criado | Repetir o passo 7 |
| `SQLSTATE[HY000] [1045] Access denied` | Usuário ou senha do MySQL | Corrigir `DB_USERNAME`/`DB_PASSWORD` no `.env` (passo 8) |
| `Vite manifest not found` | Front-end não compilado | Rodar `npm run build` (passo 11) ou `npm run dev` |
| Página sem estilo após mudar o CSS | Cache do navegador | Recarregar com `Ctrl+F5` |

## Testes
```bash
php artisan test
```
Os testes usam SQLite em memória (`phpunit.xml`), então não alteram o banco de desenvolvimento.
Lista legível de cada teste: `vendor/bin/phpunit --testdox`.

## Estrutura do código
| Pasta | Conteúdo |
|---|---|
| `app/Actions` | Regras de negócio (abrir, atribuir, alterar status, acompanhar, salvar usuário e formulário) |
| `app/Policies` | Quem pode fazer o quê com um chamado |
| `app/Livewire` + `resources/views/livewire` | Telas |
| `app/Models` e `app/Enums` | Entidades, status, perfis e prioridades |
| `database/migrations` e `database/seeders` | Estrutura do banco e dados de exemplo |
| `tests/Feature` | Testes automatizados |

## Melhorias futuras (após o MVP)
| # | Melhoria | Valor para o usuário |
|---|---|---|
| M1 | Integração com o Active Directory | Login com a conta da rede do órgão; usuários, lotação e desativação sincronizados com o AD |
| M2 | Abertura de chamados pelo WhatsApp | O requerente registra e acompanha a solicitação pelo canal que já usa no dia a dia |
| M3 | Exportação de relatórios em diversos formatos | Relatórios por área, período, status e atendente em PDF, Excel (XLSX) e CSV |
| M4 | Prazos de atendimento (SLA) por prioridade | Chamados perto de vencer ficam destacados e o painel mostra o percentual atendido no prazo |

Registradas também na Ficha 20 do Book (`docs/Book.xlsx`).

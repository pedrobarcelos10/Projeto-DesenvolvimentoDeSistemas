# ADR-001 · Stack: Laravel + Livewire + Flux (PHP) em vez de Flask

- **Status:** aceita
- **Data:** 03/11/2026 (registrada em 24/11/2026)
- **Ficha do Book:** 15 · **Âncoras:** Sommerville (2018), 6.3.1; Kunce & Goby (AWS, 2022)

## Contexto
A Ficha 8 (proposta de uma página, v2.0) definiu PHP com Laravel como tecnologia. O estudo de caso de sala
(OS_OFB_CEUB_FS) usa Flask + SQLAlchemy + SQLite. O método de avaliação aceita ferramentas equivalentes
"desde que a escolha seja justificada no Book e o artefato produzido seja o mesmo".

## Decisão
Construir o MVP com **Laravel 13** (back-end, ORM Eloquent, migrations, autenticação com Fortify),
**Livewire 4** (telas reativas renderizadas no servidor) e **Flux** (componentes de interface).

## Alternativas consideradas
1. **Flask + SQLAlchemy** (modelo de sala): menor curva com o material da disciplina, mas exigiria montar à mão
   autenticação, proteção CSRF, validação e migrations.
2. **Laravel como API REST + front-end em Vue/React:** separa as camadas, mas dobra o esforço (dois projetos)
   para um MVP individual.

## Consequências
- Autenticação, sessão, proteção contra CSRF, escape de saída (XSS) e consultas parametrizadas (SQL injection)
  vêm prontos do framework.
- As telas não dependem de uma API: o Livewire chama as regras de negócio diretamente.
- O projeto não terá API: o escopo é o uso pelas telas, e indicadores e relatórios também são telas Livewire.
- Os testes usam PHPUnit (equivalente ao pytest do modelo de sala).

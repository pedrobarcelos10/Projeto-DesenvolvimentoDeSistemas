# Evidências (Fichas 16, 17 e critério 8)

| Arquivo | O que comprova |
|---|---|
| `2026-11-24-suite-de-testes.txt` | Suíte automatizada verde: 46 testes, 150 asserções (PHPUnit `--testdox`) |
| `README.md` (este arquivo) | Riscos de segurança tratados e como reproduzir cada verificação |

Para gerar de novo: `php artisan test` (resumo) ou `vendor/bin/phpunit --testdox` (lista de cada teste).

## Segurança · OWASP Top 10:2025

| Risco (OWASP) | Como poderia acontecer no Prisma | Controle aplicado | Evidência automatizada | Como reproduzir à mão |
|---|---|---|---|---|
| **A01 · Quebra de controle de acesso** | Requerente abrir a área administrativa ou o chamado de outra pessoa | Gate `administrar`, `ChamadoPolicy` e escopo `visiveisPara()` (ADR-004) | `GerenciarUsuariosTest::test_somente_administrador_acessa_a_area_administrativa` | Entrar como `requerente@prisma.test` e abrir `/admin/usuarios` → **403 Proibido** |
| **A01 · Ação fora do papel** | Atendente ou administrador aceitar a solução no lugar do requerente | `ChamadoPolicy::alterarStatus` + verificação na Action `AlterarStatusChamado` | `AceiteSolucaoTest::test_somente_o_requerente_pode_aceitar_a_solucao` e `test_a_action_recusa_aceite_feito_por_quem_nao_abriu_o_chamado` | Entrar como atendente num chamado Solucionado: o botão "Aceitar solução" não aparece |
| **A07 · Falhas de identificação e autenticação** | Conta desativada continuar usando o sistema | Middleware `GarantirUsuarioAtivo` encerra a sessão; login recusa contas inativas | `test_usuario_desativado_nao_consegue_entrar` e `test_usuario_desativado_durante_a_sessao_e_desconectado` | Desativar um usuário em `/admin/usuarios` com ele logado em outra aba: na próxima ação ele volta ao login |
| **A07 · Cadastro indevido** | Qualquer pessoa criar conta pela internet | Não existe cadastro público; só o administrador cria contas | `GerenciarUsuariosTest::test_nao_existe_cadastro_publico` | Abrir `/register` → página não encontrada |
| **A07 · Senha errada** | Entrar com senha inválida | Fortify com senha criptografada (bcrypt) e limite de tentativas | `AuthenticationTest::test_users_can_not_authenticate_with_invalid_password` | Tentar entrar com senha errada → mensagem "E-mail ou senha incorretos." |
| **A05 · Injeção (SQL)** | Busca por título montar SQL com o texto digitado | Eloquent usa consultas parametrizadas (nenhuma concatenação de entrada em SQL) | Revisão de código: o escopo `buscar()` em `app/Models/Chamado.php` usa `where('titulo', 'like', ...)` com parâmetro | Buscar `' OR 1=1 --` na lista de chamados → nenhum resultado indevido |
| **A05 · Injeção (XSS)** | Título ou acompanhamento com `<script>` executar no navegador de outro usuário | Blade escapa toda saída com `{{ }}` | Revisão de código: nenhuma saída com `{!! !!}` vinda do usuário | Abrir chamado com título `<script>alert(1)</script>` → o texto aparece literal, sem alerta |
| **A01/A08 · CSRF** | Site externo enviar um formulário em nome do usuário logado | Token CSRF obrigatório em todo POST (Laravel + Livewire) | Comportamento padrão do framework | Enviar POST sem token → **419 Página expirada** |

### Pendências registradas
- Prints de cada verificação manual (antes/depois) para anexar ao Book: tirar durante a validação.
- Testes automatizados das demais transições da máquina de estados (abrir, atribuir, pendente, cancelar): previstos para o Incremento 4.

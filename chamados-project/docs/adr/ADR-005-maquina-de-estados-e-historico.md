# ADR-005 · Status do chamado como máquina de estados, com histórico imutável

- **Status:** aceita
- **Data:** 27/10/2026, revista em 17/11/2026 (registrada em 24/11/2026)
- **Ficha do Book:** 14 · **Âncora:** Sommerville (2018), 5.4.2 (modelos de estado)

## Contexto
A Ficha 6 (escopo mínimo) previa acompanhar se a solicitação está aberta, em andamento ou concluída, e a
hipótese H4 depende de contar as demandas por status. Um campo de status livre permitiria saltos sem sentido
(por exemplo, de "Fechado" de volta para "Aberto") e perderia a informação de quem mudou e quando.

## Decisão
- Os status ficam no enum `App\Enums\StatusChamado`: Aberto, Em atendimento, Pendente, Solucionado, Fechado e Cancelado.
- As transições permitidas ficam em `StatusChamado::transicoesPermitidas()`; Fechado e Cancelado são finais.
- **Revisão de 17/11:** só quem abriu o chamado aceita a solução (Solucionado → Fechado); a equipe pode encerrar
  diretamente um chamado ainda em andamento, informando o motivo.
- Toda mudança grava uma linha em `historicos_chamado` (quem, quando, valor anterior e novo), que nunca é editada.

## Alternativas consideradas
1. **Status em texto livre:** simples, mas aceita qualquer valor e qualquer salto.
2. **Pacote de máquina de estados:** mais recursos do que o necessário para seis estados.

## Consequências
- Transições inválidas são recusadas pela Action com mensagem clara (`ChamadoException::transicaoInvalida`).
- O histórico alimenta a linha do tempo do chamado e servirá de base para os indicadores do Incremento 4 (H4).
- Diagrama: `docs/diagramas.md`, seção "Máquina de estados".

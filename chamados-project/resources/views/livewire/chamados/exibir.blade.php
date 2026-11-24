@php
    $usuario = auth()->user();
    $podeAtender = $usuario->can('atender', $chamado);
    $statusPretendido = \App\Enums\StatusChamado::tryFrom((string) $this->statusPretendido);
@endphp

<div class="flex flex-col gap-6">
    <div>
        <flux:link :href="route('chamados.index')" wire:navigate variant="subtle" class="text-sm">
            <flux:icon.arrow-left variant="micro" class="inline" /> Voltar para chamados
        </flux:link>

        <div class="mt-2 flex flex-wrap items-center gap-2">
            <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $chamado->numero() }}</span>
            <x-chamado.status :status="$chamado->status" />
            <x-chamado.prioridade :prioridade="$chamado->prioridade" />
        </div>
        <flux:heading size="xl" level="1" class="mt-1">{{ $chamado->titulo }}</flux:heading>
    </div>

    @if (session('sucesso'))
        <flux:callout variant="success" icon="check-circle" :heading="session('sucesso')" />
    @endif

    @if ($usuario->can('avaliarSolucao', $chamado))
        <flux:callout icon="light-bulb" color="emerald">
            <flux:callout.heading>A equipe informou que seu chamado foi solucionado.</flux:callout.heading>
            <flux:callout.text>Confira a solução na linha do tempo e diga se o problema foi resolvido: aceite para fechar o chamado ou recuse para que o atendimento continue.</flux:callout.text>
        </flux:callout>
    @elseif ($chamado->status === \App\Enums\StatusChamado::Solucionado)
        <flux:callout icon="clock">
            <flux:callout.heading>Aguardando {{ $chamado->requerente->name }} avaliar a solução.</flux:callout.heading>
            <flux:callout.text>Somente quem abriu o chamado pode aceitar a solução. Se precisar, você pode retomar o atendimento.</flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Coluna principal --}}
        <div class="flex flex-col gap-6 lg:col-span-2">
            <flux:card class="flex flex-col gap-4">
                @if (filled($chamado->descricao))
                    <div>
                        <flux:heading size="sm" class="mb-1">Descrição</flux:heading>
                        <flux:text class="whitespace-pre-line">{{ $chamado->descricao }}</flux:text>
                    </div>
                @endif

                @if ($chamado->respostas_formulario)
                    <div>
                        <flux:heading size="sm" class="mb-2">{{ $chamado->formulario->nome ?? 'Respostas do formulário' }}</flux:heading>
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                            @foreach ($chamado->respostas_formulario as $resposta)
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ $resposta['rotulo'] }}</dt>
                                    <dd class="text-sm text-zinc-800 dark:text-zinc-200">
                                        @php $valor = $resposta['valor']; @endphp
                                        {{ is_array($valor) ? (implode(', ', $valor) ?: '—') : (filled($valor) ? $valor : '—') }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </flux:card>

            <section class="flex flex-col gap-4">
                <flux:heading size="lg" level="2">Linha do tempo</flux:heading>

                <ol class="relative flex flex-col gap-4 border-s border-zinc-200 ps-6 dark:border-zinc-700">
                    @foreach ($this->linhaDoTempo as $item)
                        @if ($item instanceof \App\Models\HistoricoChamado)
                            <li class="relative" wire:key="h-{{ $item->id }}">
                                <span class="absolute -start-[33px] flex size-5 items-center justify-center rounded-full bg-zinc-200 ring-4 ring-zinc-50 dark:bg-zinc-700 dark:ring-zinc-800">
                                    <flux:icon :name="$item->evento->icone()" variant="micro" class="text-zinc-500 dark:text-zinc-300" />
                                </span>
                                <flux:text size="sm">
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $item->usuario->name ?? 'Sistema' }}</span>
                                    {{ $item->descricao() }}
                                    <span class="text-zinc-400" title="{{ $item->criado_em->format('d/m/Y H:i') }}">· {{ $item->criado_em->diffForHumans() }}</span>
                                </flux:text>
                            </li>
                        @else
                            <li class="relative" wire:key="a-{{ $item->id }}">
                                <span class="absolute -start-[37px] top-1">
                                    <flux:avatar size="xs" :name="$item->usuario->name ?? '?'" :initials="$item->usuario?->initials()" color="auto" />
                                </span>
                                <div @class([
                                    'rounded-lg border p-4',
                                    'border-amber-300 bg-amber-50 dark:border-amber-700/60 dark:bg-amber-950/30' => $item->interno,
                                    'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' => ! $item->interno,
                                ])>
                                    <div class="mb-1 flex flex-wrap items-center gap-2">
                                        <flux:heading size="sm">{{ $item->usuario->name ?? 'Usuário removido' }}</flux:heading>
                                        @if ($item->interno)
                                            <flux:badge size="sm" color="amber" icon="lock-closed">Nota interna</flux:badge>
                                        @endif
                                        <flux:text size="sm" class="text-zinc-400" title="{{ $item->criado_em->format('d/m/Y H:i') }}">{{ $item->criado_em->diffForHumans() }}</flux:text>
                                    </div>
                                    <flux:text class="whitespace-pre-line">{{ $item->conteudo }}</flux:text>
                                </div>
                            </li>
                        @endif
                    @endforeach
                </ol>

                @can('acompanhar', $chamado)
                    <form wire:submit="acompanhar" class="flex flex-col gap-3">
                        <flux:textarea wire:model="conteudo" label="Adicionar acompanhamento" placeholder="Escreva uma atualização, dúvida ou informação adicional" rows="3" />
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            @if ($podeAtender)
                                <flux:checkbox wire:model="interno" label="Nota interna (visível apenas para a equipe)" />
                            @else
                                <span></span>
                            @endif
                            <flux:button type="submit" icon="chat-bubble-left-ellipsis">Enviar</flux:button>
                        </div>
                    </form>
                @else
                    <flux:text size="sm" class="italic">Este chamado está {{ mb_strtolower($chamado->status->rotulo()) }} e não aceita novos acompanhamentos.</flux:text>
                @endcan
            </section>
        </div>

        {{-- Coluna lateral --}}
        <div class="flex flex-col gap-6">
            @if ($this->acoesDeStatus !== [])
                <flux:card class="flex flex-col gap-3">
                    <flux:heading>Ações</flux:heading>
                    <div class="flex flex-col gap-2">
                        @foreach ($this->acoesDeStatus as $acao)
                            <flux:button :variant="$acao['variante']" :color="$acao['cor']" :icon="$acao['icone']" wire:click="prepararStatus('{{ $acao['status']->value }}')" class="w-full">
                                {{ $acao['rotulo'] }}
                            </flux:button>
                        @endforeach
                    </div>
                </flux:card>
            @endif

            @if ($podeAtender && ! $chamado->status->finalizado())
                <flux:card class="flex flex-col gap-3">
                    <flux:heading>Responsável</flux:heading>
                    <flux:select wire:model="responsavelId" placeholder="Sem responsável">
                        <flux:select.option value="">Sem responsável</flux:select.option>
                        @foreach ($this->atendentes as $atendente)
                            <flux:select.option :value="$atendente->id">{{ $atendente->name }}</flux:select.option>
                        @endforeach
                        @if ($chamado->responsavel && ! $this->atendentes->contains($chamado->responsavel))
                            <flux:select.option :value="$chamado->responsavel->id">{{ $chamado->responsavel->name }}</flux:select.option>
                        @endif
                    </flux:select>
                    <div class="flex gap-2">
                        <flux:button size="sm" icon="check" wire:click="atribuir" class="flex-1">Salvar</flux:button>
                        @if ($chamado->responsavel_id !== $usuario->id)
                            <flux:button size="sm" icon="hand-raised" wire:click="assumir" class="flex-1">Assumir</flux:button>
                        @endif
                    </div>
                </flux:card>
            @endif

            <flux:card>
                <flux:heading class="mb-3">Detalhes</flux:heading>
                <dl class="flex flex-col gap-3 text-sm">
                    @foreach ([
                        'Tipo' => $chamado->tipo->rotulo(),
                        'Categoria' => $chamado->categoria?->nomeCompleto() ?? '—',
                        'Área' => $chamado->area->nome.($chamado->secao ? ' · '.$chamado->secao->nome : ''),
                        'Requerente' => $chamado->requerente->name,
                        'Responsável' => $chamado->responsavel->name ?? 'Sem responsável',
                        'Aberto em' => $chamado->criado_em->format('d/m/Y H:i'),
                        'Solucionado em' => $chamado->solucionado_em?->format('d/m/Y H:i'),
                        'Encerrado em' => $chamado->fechado_em?->format('d/m/Y H:i'),
                    ] as $rotulo => $valor)
                        @if ($valor !== null)
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">{{ $rotulo }}</dt>
                                <dd class="text-end font-medium text-zinc-800 dark:text-zinc-200">{{ $valor }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </flux:card>
        </div>
    </div>

    <flux:modal name="alterar-status" class="md:w-[28rem]">
        @if ($statusPretendido)
            <form wire:submit="confirmarStatus" class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">{{ $this->rotuloDaAcao($statusPretendido) }}</flux:heading>
                    <flux:text class="mt-1">
                        O chamado passará para <strong>{{ mb_strtolower($statusPretendido->rotulo()) }}</strong>.
                    </flux:text>
                </div>

                <flux:textarea
                    wire:model="comentario"
                    :label="$this->rotuloDoComentario($statusPretendido)"
                    :badge="$this->comentarioObrigatorio($statusPretendido) ? null : 'Opcional'"
                    rows="3"
                />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Voltar</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" :variant="$statusPretendido === \App\Enums\StatusChamado::Cancelado ? 'danger' : 'primary'" icon="check">
                        Confirmar
                    </flux:button>
                </div>
            </form>
        @endif
    </flux:modal>
</div>

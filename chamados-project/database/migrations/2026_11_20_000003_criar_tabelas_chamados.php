<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chamados', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao');
            $table->string('tipo', 20)->default('requisicao');
            $table->string('status', 20)->default('aberto');
            $table->string('prioridade', 20)->default('media');

            $table->foreignId('requerente_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('area_id')->constrained('areas')->restrictOnDelete();
            $table->foreignId('secao_id')->nullable()->constrained('secoes')->nullOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();

            // Formulário usado na abertura e cópia das respostas (rótulo + valor),
            // preservada mesmo que o admin altere o formulário depois.
            $table->foreignId('formulario_id')->nullable()->constrained('formularios')->nullOnDelete();
            $table->json('respostas_formulario')->nullable();

            $table->timestamp('solucionado_em')->nullable();
            $table->timestamp('fechado_em')->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->timestamp('excluido_em')->nullable();

            $table->index(['area_id', 'status']);
            $table->index(['status', 'prioridade']);
        });

        Schema::create('acompanhamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('chamados')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('conteudo');
            // Nota interna: visível apenas para a equipe de atendimento.
            $table->boolean('interno')->default(false);
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
        });

        Schema::create('historicos_chamado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('chamados')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evento', 30);
            $table->string('valor_anterior')->nullable();
            $table->string('valor_novo')->nullable();
            $table->timestamp('criado_em')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historicos_chamado');
        Schema::dropIfExists('acompanhamentos');
        Schema::dropIfExists('chamados');
    }
};

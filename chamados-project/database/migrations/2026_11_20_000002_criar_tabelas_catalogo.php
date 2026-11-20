<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            // Área responsável por atender os chamados desta categoria.
            $table->foreignId('area_id')->constrained('areas')->restrictOnDelete();
            // Seção que recebe o chamado por padrão (opcional).
            $table->foreignId('secao_id')->nullable()->constrained('secoes')->nullOnDelete();
            $table->foreignId('categoria_pai_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
        });

        Schema::create('formularios', function (Blueprint $table) {
            $table->id();
            // O chamado gerado pelo formulário cai nesta categoria (e, por ela, na área).
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('tipo_chamado', 20)->default('requisicao');
            $table->string('prioridade_padrao', 20)->default('media');
            $table->boolean('ativo')->default(true);
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
        });

        Schema::create('campos_formulario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulario_id')->constrained('formularios')->cascadeOnDelete();
            $table->string('rotulo');
            $table->string('chave');
            $table->string('tipo', 20);
            $table->json('opcoes')->nullable();
            $table->string('texto_ajuda')->nullable();
            $table->boolean('obrigatorio')->default(false);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();

            $table->unique(['formulario_id', 'chave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campos_formulario');
        Schema::dropIfExists('formularios');
        Schema::dropIfExists('categorias');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('sigla', 20)->nullable()->unique();
            $table->text('descricao')->nullable();
            // Só áreas que atendem chamados aparecem como destino na abertura.
            $table->boolean('recebe_chamados')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
        });

        Schema::create('secoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->restrictOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();

            $table->unique(['area_id', 'nome']);
        });

        // A tabela `users` e as colunas name/email/password seguem o padrão do
        // Laravel (a autenticação do Fortify depende delas).
        Schema::table('users', function (Blueprint $table) {
            $table->string('perfil', 20)->default('requerente')->index()->after('password');
            // Lotação do usuário. Para atendentes, define a área cujos chamados ele atende.
            $table->foreignId('secao_id')->nullable()->after('perfil')->constrained('secoes')->nullOnDelete();
            $table->boolean('ativo')->default(true)->after('secao_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('secao_id');
            $table->dropIndex(['perfil']);
            $table->dropColumn(['perfil', 'ativo']);
        });

        Schema::dropIfExists('secoes');
        Schema::dropIfExists('areas');
    }
};

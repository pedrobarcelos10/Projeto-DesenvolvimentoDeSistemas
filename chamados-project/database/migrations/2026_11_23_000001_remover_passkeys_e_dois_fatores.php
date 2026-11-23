<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O Prisma não usa passkeys nem autenticação em dois fatores.
 * Remove o que o starter kit havia criado (em bancos já migrados).
 */
return new class extends Migration
{
    private const COLUNAS_2FA = ['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'];

    public function up(): void
    {
        Schema::dropIfExists('passkeys');

        $existentes = array_values(array_filter(self::COLUNAS_2FA, fn (string $coluna) => Schema::hasColumn('users', $coluna)));

        if ($existentes !== []) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($existentes));
        }
    }

    public function down(): void
    {
        // Recursos removidos de propósito; não há o que restaurar.
    }
};

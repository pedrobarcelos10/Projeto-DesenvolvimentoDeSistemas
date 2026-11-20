<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base dos models do domínio: timestamps com nomes em português.
 */
abstract class ModeloBase extends Model
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';
}

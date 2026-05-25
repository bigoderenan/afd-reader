<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Modelo simples baseado em array para manter a primeira versão compatível com hospedagem comum.
 * Evolua para entidades ricas conforme o domínio do layout AFD usado pelo equipamento.
 */
final class AfdArquivo
{
    public function __construct(public array $attributes = [])
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}

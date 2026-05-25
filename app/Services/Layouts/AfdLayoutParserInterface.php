<?php

declare(strict_types=1);

namespace App\Services\Layouts;

interface AfdLayoutParserInterface
{
    /**
     * Retorna um array padronizado com metadados da linha.
     * O mapeamento legal/fiscal deve ser ajustado conforme o manual oficial do equipamento/layout usado.
     *
     * @return array<string,mixed>
     */
    public function parseLine(string $line, int $lineNumber): array;

    public function layoutCode(): string;
}

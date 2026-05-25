<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Services\AfdParser;

$file = $argv[1] ?? null;
if (!$file || !is_file($file)) {
    fwrite(STDERR, "Uso: php tools/test-parser.php caminho/do/arquivo.txt\n");
    exit(1);
}

$data = (new AfdParser())->parse($file, basename($file));

echo json_encode([
    'empresa' => $data['empresa'] ?? [],
    'relogio' => $data['relogio'] ?? [],
    'arquivo' => [
        'linhas' => $data['arquivo']['numeroLinhas'] ?? 0,
        'primeiroNsr' => $data['arquivo']['primeiroNsr'] ?? null,
        'ultimoNsr' => $data['arquivo']['ultimoNsr'] ?? null,
        'integridade' => $data['arquivo']['integridade'] ?? null,
        'contadores' => $data['arquivo']['contadores'] ?? [],
    ],
    'usuarios' => count($data['usuarios'] ?? []),
    'marcacoes' => count($data['marcacoes'] ?? []),
    'eventosCadastro' => count($data['eventosCadastro'] ?? []),
    'erros' => array_slice($data['erros'] ?? [], 0, 5),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

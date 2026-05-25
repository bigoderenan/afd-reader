<?php

declare(strict_types=1);

namespace App\Services\Layouts;

use App\Helpers\Format;

class AfdLayoutDefaultParser implements AfdLayoutParserInterface
{
    public function layoutCode(): string
    {
        return 'GENÉRICO';
    }

    public function parseLine(string $line, int $lineNumber): array
    {
        $raw = $line;
        $line = trim($line);
        $errors = [];

        if ($line === '') {
            return $this->base($lineNumber, $raw, null, 'vazia', 'Linha vazia', null, null, null, null, 'erro', ['Linha vazia.']);
        }

        if (!preg_match('/^(\d{9})/', $line, $m)) {
            if ($this->looksLikeSignature($line)) {
                return $this->base($lineNumber, $raw, null, 'assinatura', 'Assinatura digital do arquivo', null, null, null, null, 'ok');
            }
            return $this->base($lineNumber, $raw, null, 'generico', 'Registro sem NSR identificado', null, null, null, null, 'erro', ['NSR não identificado.']);
        }

        $nsr = $m[1];
        $typeCode = substr($line, 9, 1) ?: null;
        [$tipo, $descricao] = $this->classify($typeCode, $line, $nsr);
        $date = $this->extractDate($line);
        $time = $this->extractTime($line);
        $pisCpf = $this->extractPisCpf($line, $typeCode);
        $name = $this->extractName($line);

        if ($date !== null && !Format::validDate($date)) {
            $errors[] = 'Data inválida.';
        }
        if ($time !== null && !Format::validTime($time)) {
            $errors[] = 'Hora inválida.';
        }

        return $this->base(
            $lineNumber,
            $raw,
            $nsr,
            $tipo,
            $descricao,
            $date,
            $time,
            $pisCpf,
            $name,
            $errors === [] ? 'ok' : 'erro',
            $errors,
            $typeCode
        );
    }

    /** @return array<string,mixed> */
    protected function base(int $lineNumber, string $raw, ?string $nsr, string $tipo, string $descricao, ?string $date, ?string $time, ?string $pisCpf, ?string $name, string $status, array $errors = [], ?string $typeCode = null, array $extra = []): array
    {
        return $extra + [
            'linha' => $lineNumber,
            'nsr' => $nsr,
            'codigoTipo' => $typeCode,
            'tipo' => $tipo,
            'tipoRegistro' => $this->humanType($tipo),
            'data' => $date,
            'hora' => $time,
            'pisCpf' => $pisCpf,
            'nome' => $name,
            'descricao' => $descricao,
            'conteudoOriginal' => $raw,
            'status' => $status,
            'erros' => $errors,
        ];
    }

    protected function humanType(string $tipo): string
    {
        return match ($tipo) {
            'cabecalho' => 'Cabeçalho do arquivo',
            'evento_empresa' => 'Edição da empresa',
            'evento_cadastro' => 'Edição de cadastro',
            'alteracao_horario' => 'Alteração de horário',
            'marcacao' => 'Marcação de ponto',
            'operacional' => 'Registro operacional',
            'trailer' => 'Trailer/encerramento',
            'assinatura' => 'Assinatura digital',
            'vazia' => 'Linha vazia',
            default => 'Registro genérico',
        };
    }

    /** @return array{0:string,1:string} */
    protected function classify(?string $typeCode, string $line, ?string $nsr = null): array
    {
        $upper = $this->upper($line);

        if ($nsr === '999999999' || $typeCode === '0') {
            return ['trailer', 'Registro de encerramento'];
        }
        if (str_contains($upper, 'INCLUSAO') || str_contains($upper, 'INCLUSÃO') || str_contains($upper, 'ALTERACAO') || str_contains($upper, 'ALTERAÇÃO') || str_contains($upper, 'EXCLUSAO') || str_contains($upper, 'EXCLUSÃO')) {
            return ['evento_cadastro', $this->eventDescription($upper)];
        }
        if (str_contains($upper, 'CNPJ') || str_contains($upper, 'RAZAO') || str_contains($upper, 'RAZÃO')) {
            return ['evento_empresa', 'Evento ou dado da empresa'];
        }
        if (str_contains($upper, 'HORARIO') || str_contains($upper, 'HORÁRIO') || str_contains($upper, 'RELOGIO') || str_contains($upper, 'RELÓGIO')) {
            return ['alteracao_horario', 'Alteração ou ajuste de horário do equipamento'];
        }

        return match ($typeCode) {
            '1' => ['cabecalho', 'Cabeçalho do arquivo AFD'],
            '2' => ['evento_empresa', 'Edição de dados da empresa'],
            '3' => ['evento_cadastro', 'Edição de cadastro de empregado'],
            '4' => ['alteracao_horario', 'Ajuste de data/hora do relógio'],
            '5' => ['marcacao', 'Marcação de ponto'],
            '6', '7', '8' => ['operacional', 'Registro operacional do equipamento'],
            '9' => ['trailer', 'Registro de encerramento'],
            default => ['generico', 'Registro importado em modo genérico'],
        };
    }

    protected function eventDescription(string $upper): string
    {
        if (str_contains($upper, 'EXCLUSAO') || str_contains($upper, 'EXCLUSÃO')) {
            return 'Exclusão de cadastro';
        }
        if (str_contains($upper, 'INCLUSAO') || str_contains($upper, 'INCLUSÃO')) {
            return 'Inclusão de cadastro';
        }
        if (str_contains($upper, 'ALTERACAO') || str_contains($upper, 'ALTERAÇÃO')) {
            return 'Alteração de cadastro';
        }
        return 'Edição de cadastro';
    }

    protected function extractDate(string $line): ?string
    {
        if (preg_match('/(20\d{2})[-\/](\d{2})[-\/](\d{2})T/', $line, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }
        if (preg_match('/(20\d{2})[-\/](\d{2})[-\/](\d{2})/', $line, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }
        if (preg_match('/\b(\d{2})[-\/](\d{2})[-\/](20\d{2})\b/', $line, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        if (preg_match('/^(\d{9})(\d)(\d{2})(\d{2})(20\d{2})/', $line, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[5], (int)$m[4], (int)$m[3]);
        }
        return null;
    }

    protected function extractTime(string $line): ?string
    {
        if (preg_match('/T([01]\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?/', $line, $m)) {
            return $m[1] . ':' . $m[2];
        }
        if (preg_match('/\b([01]\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?\b/', $line, $m)) {
            return $m[1] . ':' . $m[2];
        }
        if (preg_match('/^(\d{9})(\d)(\d{8})([01]\d|2[0-3])([0-5]\d)/', $line, $m)) {
            return $m[4] . ':' . $m[5];
        }
        return null;
    }

    protected function extractPisCpf(string $line, ?string $typeCode = null): ?string
    {
        if (preg_match('/^\d{9}\d\d{8}\d{4}(\d{11,12})/', $line, $m)) {
            return $m[1];
        }
        if (preg_match('/^\d{9}\d20\d{2}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{4}[A-Z]?(\d{11,12})/', $line, $m)) {
            return $m[1];
        }

        preg_match_all('/\d{11,14}/', $line, $matches);
        foreach ($matches[0] as $candidate) {
            if ($typeCode === '1' && strlen($candidate) === 14) {
                continue;
            }
            if (strlen($candidate) === 11 || strlen($candidate) === 12) {
                return $candidate;
            }
        }
        return null;
    }

    protected function extractName(string $line): ?string
    {
        $normalized = preg_replace('/[^A-Za-zÀ-ÿ\s]/u', ' ', $line) ?? '';
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized) ?? '');
        if (strlen($normalized) < 5) {
            return null;
        }
        $ignore = ['CNPJ', 'CPF', 'PIS', 'NSR', 'AFD', 'REP', 'REGISTRO'];
        foreach ($ignore as $word) {
            $normalized = trim(str_ireplace($word, '', $normalized));
        }
        return $normalized !== '' ? $this->upper($normalized) : null;
    }

    protected function upper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }

    protected function looksLikeSignature(string $line): bool
    {
        return preg_match('/^[A-Za-z0-9+\/]{20,}={0,2}\s*$/', $line) === 1;
    }
}

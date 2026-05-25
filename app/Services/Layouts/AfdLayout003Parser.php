<?php

declare(strict_types=1);

namespace App\Services\Layouts;

use App\Helpers\Format;

class AfdLayout003Parser extends AfdLayoutDefaultParser
{
    public function layoutCode(): string
    {
        return '003';
    }

    public static function seemsCompatible(array $previewLines): bool
    {
        foreach ($previewLines as $line) {
            $line = trim($line);
            if (preg_match('/^0000000001[12]\d{14}/', $line)) {
                return true;
            }
            if (str_contains($line, '003') && preg_match('/\d{14}/', $line)) {
                return true;
            }
            if (preg_match('/^\d{9}[1234560]20\d{2}-\d{2}-\d{2}T/', $line)) {
                return true;
            }
        }
        return false;
    }

    public function parseLine(string $line, int $lineNumber): array
    {
        $raw = $line;
        $line = rtrim($line, "\r\n");
        $trimmed = trim($line);

        if ($trimmed === '') {
            return $this->base($lineNumber, $raw, null, 'vazia', 'Linha vazia', null, null, null, null, 'erro', ['Linha vazia.']);
        }

        if (!preg_match('/^(\d{9})([0-9])/', $trimmed, $m)) {
            if ($this->looksLikeSignature($trimmed)) {
                return $this->base($lineNumber, $raw, null, 'assinatura', 'Assinatura digital do arquivo', null, null, null, null, 'ok');
            }
            return $this->base($lineNumber, $raw, null, 'generico', 'Registro sem NSR identificado', null, null, null, null, 'erro', ['NSR não identificado.']);
        }

        $nsr = $m[1];
        $typeCode = $m[2];
        $errors = [];

        if ($nsr === '999999999' || $typeCode === '0') {
            return $this->base($lineNumber, $raw, $nsr, 'trailer', 'Registro de encerramento do arquivo', null, null, null, null, 'ok', [], $typeCode, [
                'totaisTrailer' => $this->parseTrailerTotals($trimmed),
            ]);
        }

        $date = $this->extractDate($trimmed);
        $time = $this->extractTime($trimmed);
        if ($date !== null && !Format::validDate($date)) {
            $errors[] = 'Data inválida.';
        }
        if ($time !== null && !Format::validTime($time)) {
            $errors[] = 'Hora inválida.';
        }

        return match ($typeCode) {
            '1' => $this->parseHeader($trimmed, $lineNumber, $raw, $errors),
            '2' => $this->base($lineNumber, $raw, $nsr, 'evento_empresa', 'Edição de dados da empresa', $date, $time, null, null, $errors === [] ? 'ok' : 'erro', $errors, $typeCode),
            '3' => $this->base($lineNumber, $raw, $nsr, 'marcacao', 'Marcação de ponto', $date, $time, $this->fixedDigits($trimmed, 34, 12), null, $errors === [] ? 'ok' : 'erro', $errors, $typeCode),
            '4' => $this->base($lineNumber, $raw, $nsr, 'alteracao_horario', 'Ajuste de data/hora do relógio', $date, $time, $this->extractLastPis($trimmed), null, $errors === [] ? 'ok' : 'erro', $errors, $typeCode, [
                'dataHoraAnterior' => $this->extractSecondTimestamp($trimmed),
            ]),
            '5' => $this->parseEmployeeEvent($trimmed, $lineNumber, $raw, $date, $time, $errors),
            '6' => $this->base($lineNumber, $raw, $nsr, 'operacional', 'Registro operacional do equipamento' . ($this->fixedDigits($trimmed, 34, 2) ? ' - código ' . $this->fixedDigits($trimmed, 34, 2) : ''), $date, $time, null, null, $errors === [] ? 'ok' : 'erro', $errors, $typeCode),
            default => $this->base($lineNumber, $raw, $nsr, 'generico', 'Registro importado em modo genérico', $date, $time, $this->extractPisCpf($trimmed, $typeCode), $this->extractName($trimmed), $errors === [] ? 'ok' : 'erro', $errors, $typeCode),
        };
    }

    private function parseHeader(string $line, int $lineNumber, string $raw, array $errors): array
    {
        $typeCode = '1';
        $typeFlag = substr($line, 10, 1) ?: '';
        $cnpjCpf = $this->fixedDigits($line, 11, 14);
        $cnoCaepf = $this->fixedDigits($line, 25, 14);
        $razao = $this->fixedText($line, 39, 150);
        $serial = $this->fixedDigits($line, 189, 17);
        $dataInicial = $this->fixedDate($line, 206);
        $dataFinal = $this->fixedDate($line, 216);
        $dataHoraGeracao = trim(substr($line, 226, 24)) ?: null;
        $layout = trim(substr($line, 250, 3)) ?: '003';
        $fabricanteFlag = substr($line, 253, 1) ?: '';
        $cnpjFabricante = $this->fixedDigits($line, 254, 14);
        $modelo = $this->fixedText($line, 268, 30);

        return $this->base($lineNumber, $raw, substr($line, 0, 9), 'cabecalho', 'Cabeçalho do arquivo AFD', $dataInicial, null, null, $razao, $errors === [] ? 'ok' : 'erro', $errors, $typeCode, [
            'tipoEmpregador' => $typeFlag === '2' ? 'CPF' : 'CNPJ',
            'cnpjCpf' => $cnpjCpf,
            'cnoCaepf' => $cnoCaepf,
            'razaoSocial' => $razao,
            'serial' => $serial,
            'dataInicial' => $dataInicial,
            'dataFinal' => $dataFinal,
            'dataHoraGeracao' => $dataHoraGeracao,
            'layoutArquivo' => $layout,
            'tipoFabricante' => $fabricanteFlag === '2' ? 'CPF' : 'CNPJ',
            'cnpjCpfFabricante' => $cnpjFabricante,
            'modelo' => $modelo,
        ]);
    }

    private function parseEmployeeEvent(string $line, int $lineNumber, string $raw, ?string $date, ?string $time, array $errors): array
    {
        $action = strtoupper(substr($line, 34, 1) ?: 'A');
        $pis = $this->fixedDigits($line, 35, 12);
        $name = $this->fixedText($line, 47, 50);

        [$tipo, $descricaoBase] = match ($action) {
            'I' => ['inclusao', 'Inclusão de cadastro'],
            'E' => ['exclusao', 'Exclusão de cadastro'],
            default => ['alteracao', 'Alteração de cadastro'],
        };

        return $this->base($lineNumber, $raw, substr($line, 0, 9), 'evento_cadastro', $descricaoBase . ($name ? ' - ' . $name : ''), $date, $time, $pis, $name, $errors === [] ? 'ok' : 'erro', $errors, '5', [
            'acaoCadastro' => $tipo,
            'codigoEventoCadastro' => $action,
        ]);
    }

    private function fixedText(string $line, int $start, int $length): ?string
    {
        $value = trim(substr($line, $start, $length));
        return $value !== '' ? $value : null;
    }

    private function fixedDigits(string $line, int $start, int $length): ?string
    {
        $value = preg_replace('/\D+/', '', substr($line, $start, $length)) ?? '';
        return $value !== '' ? $value : null;
    }

    private function fixedDate(string $line, int $start): ?string
    {
        $value = substr($line, $start, 10);
        return preg_match('/^20\d{2}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    private function extractSecondTimestamp(string $line): ?string
    {
        if (preg_match_all('/20\d{2}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{4}/', $line, $m) && isset($m[0][1])) {
            return $m[0][1];
        }
        return null;
    }

    private function extractLastPis(string $line): ?string
    {
        if (preg_match_all('/\d{11,12}/', $line, $m) && !empty($m[0])) {
            return end($m[0]) ?: null;
        }
        return null;
    }

    /** @return array<string,int> */
    private function parseTrailerTotals(string $line): array
    {
        return [
            'eventosEmpresa' => (int)substr($line, 10, 9),
            'marcacoes' => (int)substr($line, 19, 9),
            'alteracoesHorario' => (int)substr($line, 28, 9),
            'eventosCadastro' => (int)substr($line, 37, 9),
            'eventosOperacionais' => (int)substr($line, 46, 9),
        ];
    }
}

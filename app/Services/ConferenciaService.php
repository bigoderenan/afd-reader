<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;

final class ConferenciaService
{
    /** @return array<string,array<int,array<string,mixed>>> */
    public function runAll(array $data): array
    {
        return [
            'marcacoes' => $this->marcacoesSuspeitas($data),
            'empresa' => $this->edicoesEmpresaSuspeitas($data),
            'horario' => $this->alteracoesHorarioSuspeitas($data),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function marcacoesSuspeitas(array $data): array
    {
        $items = [];
        $marks = $data['marcacoes'] ?? [];
        usort($marks, static fn($a, $b) => strcmp(($a['pisCpf'] ?? '') . ($a['data'] ?? '') . ($a['hora'] ?? ''), ($b['pisCpf'] ?? '') . ($b['data'] ?? '') . ($b['hora'] ?? '')));

        $byUserDay = [];
        foreach ($marks as $mark) {
            if (empty($mark['pisCpf']) || empty($mark['data'])) {
                $items[] = $this->item('Marcação sem funcionário vinculado', $mark, 'Média', 'Revisar cadastro');
                continue;
            }
            $byUserDay[$mark['pisCpf']][$mark['data']][] = $mark;
        }

        foreach ($byUserDay as $pisCpf => $days) {
            foreach ($days as $date => $dayMarks) {
                usort($dayMarks, static fn($a, $b) => strcmp($a['hora'] ?? '', $b['hora'] ?? ''));
                if (count($dayMarks) % 2 !== 0) {
                    $items[] = $this->item('Número ímpar de marcações no dia', $dayMarks[0], 'Alta', 'Conferir espelho');
                }
                if (count($dayMarks) > 8) {
                    $items[] = $this->item('Dia com muitas marcações', $dayMarks[0], 'Média', 'Conferir batidas');
                }
                $weekday = (int)(new DateTimeImmutable($date))->format('w');
                if ($weekday === 0) {
                    $items[] = $this->item('Marcação em domingo', $dayMarks[0], 'Baixa', 'Validar jornada');
                }
                for ($i = 1; $i < count($dayMarks); $i++) {
                    $diff = abs($this->toMinutes($dayMarks[$i]['hora'] ?? '00:00') - $this->toMinutes($dayMarks[$i - 1]['hora'] ?? '00:00'));
                    if ($diff <= 3) {
                        $items[] = $this->item('Batidas duplicadas em intervalo muito curto', $dayMarks[$i], 'Alta', 'Comparar NSR');
                    }
                }
            }
        }

        foreach (($data['arquivo']['quebrasNsr'] ?? []) as $break) {
            $items[] = [
                'tipo' => 'NSR fora de sequência',
                'data' => null,
                'hora' => null,
                'nsr' => $break['encontrado'] ?? null,
                'descricao' => 'Esperado ' . ($break['esperado'] ?? '--') . ', encontrado ' . ($break['encontrado'] ?? '--'),
                'gravidade' => 'Alta',
                'acao' => 'Revisar integridade',
            ];
        }

        foreach (($data['linhas'] ?? []) as $line) {
            if (($line['status'] ?? '') === 'erro') {
                $items[] = [
                    'tipo' => 'Linha inválida',
                    'data' => $line['data'] ?? null,
                    'hora' => $line['hora'] ?? null,
                    'nsr' => $line['nsr'] ?? null,
                    'descricao' => implode('; ', $line['erros'] ?? []),
                    'gravidade' => 'Média',
                    'acao' => 'Abrir linha original',
                ];
            }
        }

        return $items;
    }

    /** @return array<int,array<string,mixed>> */
    public function edicoesEmpresaSuspeitas(array $data): array
    {
        $items = [];
        foreach ($data['eventosEmpresa'] ?? [] as $event) {
            $items[] = $this->item('Edição de dados da empresa', $event, 'Média', 'Comparar com cabeçalho');
        }
        return $items;
    }

    /** @return array<int,array<string,mixed>> */
    public function alteracoesHorarioSuspeitas(array $data): array
    {
        $items = [];
        foreach ($data['alteracoesHorario'] ?? [] as $event) {
            $hour = $this->toMinutes($event['hora'] ?? '00:00');
            $severity = ($hour < 8 * 60 || $hour > 18 * 60) ? 'Alta' : 'Média';
            $items[] = $this->item('Mudança no horário do relógio', $event, $severity, 'Comparar marcações próximas');
        }
        return $items;
    }

    private function item(string $type, array $source, string $severity, string $action): array
    {
        return [
            'tipo' => $type,
            'data' => $source['data'] ?? null,
            'hora' => $source['hora'] ?? null,
            'nsr' => $source['nsr'] ?? null,
            'descricao' => $source['descricao'] ?? ($source['nome'] ?? 'Registro para conferência'),
            'gravidade' => $severity,
            'acao' => $action,
        ];
    }

    private function toMinutes(string $time): int
    {
        if (!preg_match('/^(\d{2}):(\d{2})$/', $time, $m)) {
            return 0;
        }
        return ((int)$m[1] * 60) + (int)$m[2];
    }
}

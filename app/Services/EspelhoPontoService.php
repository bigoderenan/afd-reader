<?php

namespace App\Services;

class EspelhoPontoService
{
    public function gerar(array $parsed, string $pis, int $mes, int $ano, array $jornada): array
    {
        $jornada = (new JornadaService())->normalize($jornada);

        $usuario = $parsed['usuarios'][$pis] ?? [
            'pis' => $pis,
            'nome' => $pis,
            'marcacoes' => [],
            'eventos' => [],
        ];

        $marcacoes = $usuario['marcacoes'] ?? [];
        $porDia = [];

        foreach ($marcacoes as $m) {
            $data = (string)($m['data'] ?? '');

            if (!$this->sameMonth($data, $mes, $ano)) {
                continue;
            }

            $porDia[$data][] = $m;
        }

        foreach ($porDia as &$items) {
            usort($items, static function ($a, $b) {
                return strcmp((string)($a['hora'] ?? ''), (string)($b['hora'] ?? ''));
            });
        }
        unset($items);

        $diasMes = (int)date('t', strtotime(sprintf('%04d-%02d-01', $ano, $mes)));

        $rows = [];
        $totalTrabalhado = 0;
        $totalFalta = 0;
        $totalExtra = 0;
        $invalidadas = 0;

        for ($dia = 1; $dia <= $diasMes; $dia++) {
            $data = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
            $dow = (int)date('N', strtotime($data));

            $isUtil = in_array($dow, $jornada['dias_uteis'] ?? [1, 2, 3, 4, 5], true);

            $minutosPorDia = $jornada['minutos_por_dia'] ?? [
                1 => 540, // Segunda: 09:00
                2 => 540, // Terça: 09:00
                3 => 540, // Quarta: 09:00
                4 => 540, // Quinta: 09:00
                5 => 480, // Sexta: 08:00
                6 => 0,
                7 => 0,
            ];

            $esperado = $isUtil
                ? (int)($minutosPorDia[$dow] ?? $jornada['diaria_minutos'] ?? 480)
                : 0;

            $items = $porDia[$data] ?? [];

            $batidas = array_map(static fn ($m) => (string)($m['hora'] ?? ''), $items);

            $pares = $this->montarPares($data, $batidas);

            $trabalhado = $pares['minutos'];
            $comentario = $pares['comentario'];

            if ($pares['incompleto']) {
                $invalidadas++;
            }

            $tolerancia = (int)($jornada['tolerancia_minutos'] ?? 10);

            $falta = 0;
            $extra = 0;

            if ($esperado > 0) {
                if (count($batidas) === 0) {
                    $falta = $esperado;
                } elseif (($esperado - $trabalhado) > $tolerancia) {
                    $falta = $esperado - $trabalhado;
                } elseif (($trabalhado - $esperado) > $tolerancia) {
                    $extra = $trabalhado - $esperado;
                }
            } elseif ($trabalhado > 0) {
                $extra = $trabalhado;
            }

            $totalTrabalhado += $trabalhado;
            $totalFalta += $falta;
            $totalExtra += $extra;

            $rows[] = [
                'data_iso' => $data,
                'data' => date('d/m/Y', strtotime($data)),
                'dia' => $this->diaSemana($dow),
                'batidas' => $batidas,
                'entrada1' => $batidas[0] ?? '',
                'saida1' => $batidas[1] ?? '',
                'entrada2' => $batidas[2] ?? '',
                'saida2' => $batidas[3] ?? '',
                'entrada3' => $batidas[4] ?? '',
                'saida3' => $batidas[5] ?? '',
                'tempo' => $trabalhado > 0 ? JornadaService::minutesToHour($trabalhado) : '',
                'esperado' => $esperado > 0 ? JornadaService::minutesToHour($esperado) : '--',
                'comentario' => $comentario,
                'falta' => $falta > 0 ? JornadaService::minutesToHour($falta) : '--',
                'extra' => $extra > 0 ? JornadaService::minutesToHour($extra) : '--',
                'falta_minutos' => $falta,
                'extra_minutos' => $extra,
            ];
        }

        return [
            'usuario' => $usuario,
            'rows' => $rows,
            'totais' => [
                'trabalhado' => JornadaService::minutesToHour($totalTrabalhado),
                'faltas' => JornadaService::minutesToHour($totalFalta),
                'extras' => JornadaService::minutesToHour($totalExtra),
            ],
            'invalidadas' => $invalidadas,
        ];
    }

    public function periodoPadrao(array $usuario): array
    {
        $marks = $usuario['marcacoes'] ?? [];

        if (!$marks) {
            return [(int)date('m'), (int)date('Y')];
        }

        usort($marks, static function ($a, $b) {
            $da = (string)($a['datetime'] ?? (($a['data'] ?? '') . ' ' . ($a['hora'] ?? '')));
            $db = (string)($b['datetime'] ?? (($b['data'] ?? '') . ' ' . ($b['hora'] ?? '')));

            return strcmp($da, $db);
        });

        $ultima = $marks[count($marks) - 1]['data'] ?? date('Y-m-d');

        return [
            (int)substr($ultima, 5, 2),
            (int)substr($ultima, 0, 4),
        ];
    }

    private function sameMonth(string $data, int $mes, int $ano): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)
            && (int)substr($data, 0, 4) === $ano
            && (int)substr($data, 5, 2) === $mes;
    }

    private function montarPares(string $data, array $batidas): array
    {
        $minutos = 0;
        $incompleto = false;

        for ($i = 0; $i < count($batidas); $i += 2) {
            $entrada = $batidas[$i] ?? null;
            $saida = $batidas[$i + 1] ?? null;

            if (!$entrada || !$saida) {
                $incompleto = true;
                continue;
            }

            $start = strtotime($data . ' ' . $entrada);
            $end = strtotime($data . ' ' . $saida);

            if ($start === false || $end === false || $end <= $start) {
                $incompleto = true;
                continue;
            }

            $minutos += (int)(($end - $start) / 60);
        }

        return [
            'minutos' => $minutos,
            'incompleto' => $incompleto,
            'comentario' => '',
        ];
    }

    private function diaSemana(int $dow): string
    {
        return [
            1 => 'SEG',
            2 => 'TER',
            3 => 'QUA',
            4 => 'QUI',
            5 => 'SEX',
            6 => 'SAB',
            7 => 'DOM',
        ][$dow] ?? '';
    }
}
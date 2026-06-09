<?php

namespace App\Services;

class EspelhoPontoService
{
    private const SLOT_COUNT = 4;
    private const PLACEHOLDER = '00:00';

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
        $manualService = new MarcacaoManualService();
        $ajustesManuais = $manualService->forPis($pis);
        $ajustesManuaisMes = [];

        foreach ($ajustesManuais as $dataAjuste => $_ajuste) {
            $dataAjuste = (string)$dataAjuste;
            if ($this->sameMonth($dataAjuste, $mes, $ano)) {
                $ajuste = $manualService->getDay($pis, $dataAjuste);
                if ($ajuste !== null && $manualService->hasEffectiveBatidas($ajuste['batidas'] ?? [])) {
                    $ajustesManuaisMes[$dataAjuste] = $ajuste;
                }
            }
        }

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
            $manual = isset($ajustesManuaisMes[$data]);

            if ($manual) {
                $batidasOrigem = $ajustesManuaisMes[$data]['batidas'] ?? [];
                $comentarioManual = (string)($ajustesManuaisMes[$data]['comentario'] ?? '');
                $slots = $this->normalizarSlotsManuais($batidasOrigem);
            } else {
                $batidasOrigem = array_map(static fn ($m) => (string)($m['hora'] ?? ''), $items);
                $comentarioManual = '';
                $slots = $this->normalizarSlotsAfd($batidasOrigem);
            }

            $batidasEfetivas = $this->contarBatidasEfetivas($slots);
            $pares = $this->montarPares($data, $slots);

            $trabalhado = $pares['minutos'];
            $comentarios = [];
            if ($manual) {
                $comentarios[] = $comentarioManual !== '' ? $comentarioManual : 'Ajuste manual';
            }
            if ((string)$pares['comentario'] !== '') {
                $comentarios[] = (string)$pares['comentario'];
            }

            $extrasIgnoradas = array_values(array_slice($this->filtrarHorasValidas($batidasOrigem), self::SLOT_COUNT));
            if (!$manual && $extrasIgnoradas) {
                $comentarios[] = 'Marcações acima de 4 posições: ' . implode(', ', $extrasIgnoradas);
            }

            $comentario = implode(' | ', array_unique(array_filter($comentarios, static fn ($item) => trim((string)$item) !== '')));
            $batidasDisplay = $this->slotsParaDisplay($slots, $manual, $batidasEfetivas);

            $marcacaoPendente = $pares['incompleto'] && $batidasEfetivas > 0;
            if ($pares['incompleto']) {
                $invalidadas++;
            }

            $tolerancia = (int)($jornada['tolerancia_minutos'] ?? 10);

            $falta = 0;
            $extra = 0;

            // Quando há marcação incompleta, o dia fica pendente de correção manual.
            // Isso evita gerar falta ou hora extra falsa por causa de batida ausente.
            if (!$marcacaoPendente) {
                if ($esperado > 0) {
                    if ($batidasEfetivas === 0) {
                        $falta = $esperado;
                    } elseif (($esperado - $trabalhado) > $tolerancia) {
                        $falta = $esperado - $trabalhado;
                    } elseif (($trabalhado - $esperado) > $tolerancia) {
                        $extra = $trabalhado - $esperado;
                    }
                } elseif ($trabalhado > 0) {
                    $extra = $trabalhado;
                }
            }

            $totalTrabalhado += $trabalhado;
            $totalFalta += $falta;
            $totalExtra += $extra;

            $rows[] = [
                'data_iso' => $data,
                'data' => date('d/m/Y', strtotime($data)),
                'dia' => $this->diaSemana($dow),
                'batidas' => $batidasDisplay,
                'batidas_raw' => $slots,
                'manual' => $manual,
                'entrada1' => $batidasDisplay[0] ?? '',
                'saida1' => $batidasDisplay[1] ?? '',
                'entrada2' => $batidasDisplay[2] ?? '',
                'saida2' => $batidasDisplay[3] ?? '',
                'tempo' => $trabalhado > 0 ? JornadaService::minutesToHour($trabalhado) : '',
                'esperado' => $esperado > 0 ? JornadaService::minutesToHour($esperado) : '--',
                'comentario' => $comentario,
                'falta' => $falta > 0 ? JornadaService::minutesToHour($falta) : '--',
                'extra' => $extra > 0 ? JornadaService::minutesToHour($extra) : '--',
                'falta_minutos' => $falta,
                'extra_minutos' => $extra,
                'marcacao_pendente' => $marcacaoPendente,
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

        for ($i = 0; $i < self::SLOT_COUNT; $i += 2) {
            $entrada = $batidas[$i] ?? self::PLACEHOLDER;
            $saida = $batidas[$i + 1] ?? self::PLACEHOLDER;
            $entradaValida = $this->isEffectiveHora((string)$entrada);
            $saidaValida = $this->isEffectiveHora((string)$saida);

            if (!$entradaValida && !$saidaValida) {
                continue;
            }

            if (!$entradaValida || !$saidaValida) {
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
            'comentario' => $incompleto ? 'Marcação incompleta' : '',
        ];
    }

    private function normalizarSlotsManuais(array $batidas): array
    {
        $slots = [];
        for ($i = 0; $i < self::SLOT_COUNT; $i++) {
            $hora = trim((string)($batidas[$i] ?? ''));
            $slots[] = $this->isHoraValida($hora) ? $hora : self::PLACEHOLDER;
        }

        return $slots;
    }

    /**
     * Monta as quatro posições padrão a partir das marcações do AFD.
     *
     * Com quatro ou mais marcações, preserva a ordem cronológica das quatro
     * primeiras. Com uma, duas ou três marcações, tenta posicionar pelo horário
     * provável do expediente: manhã, saída para almoço, retorno do almoço e saída
     * final. Isso evita o deslocamento mostrado no espelho quando falta a primeira
     * marcação do dia.
     */
    private function normalizarSlotsAfd(array $batidas): array
    {
        $validas = $this->filtrarHorasValidas($batidas);
        $total = count($validas);

        if ($total === 0) {
            return array_fill(0, self::SLOT_COUNT, self::PLACEHOLDER);
        }

        if ($total >= self::SLOT_COUNT) {
            return array_slice($validas, 0, self::SLOT_COUNT);
        }

        return $this->posicionarSlotsPorHorario($validas);
    }

    private function posicionarSlotsPorHorario(array $validas): array
    {
        $slots = array_fill(0, self::SLOT_COUNT, self::PLACEHOLDER);

        foreach ($validas as $hora) {
            $preferido = $this->slotPreferidoPorHorario($hora);
            $slot = $this->slotDisponivelMaisProximo($slots, $preferido);
            if ($slot !== null) {
                $slots[$slot] = $hora;
            }
        }

        return $slots;
    }

    private function slotPreferidoPorHorario(string $hora): int
    {
        $minutos = $this->minutesFromHour($hora);

        if ($minutos <= 630) { // até 10:30: Entrada 1
            return 0;
        }
        if ($minutos <= 750) { // até 12:30: Saída 1
            return 1;
        }
        if ($minutos < 900) { // antes de 15:00: Entrada 2
            return 2;
        }

        return 3; // 15:00 em diante: Saída 2
    }

    private function slotDisponivelMaisProximo(array $slots, int $preferido): ?int
    {
        if (($slots[$preferido] ?? self::PLACEHOLDER) === self::PLACEHOLDER) {
            return $preferido;
        }

        for ($distancia = 1; $distancia < self::SLOT_COUNT; $distancia++) {
            $direita = $preferido + $distancia;
            if ($direita < self::SLOT_COUNT && ($slots[$direita] ?? self::PLACEHOLDER) === self::PLACEHOLDER) {
                return $direita;
            }

            $esquerda = $preferido - $distancia;
            if ($esquerda >= 0 && ($slots[$esquerda] ?? self::PLACEHOLDER) === self::PLACEHOLDER) {
                return $esquerda;
            }
        }

        return null;
    }

    private function filtrarHorasValidas(array $batidas): array
    {
        $validas = [];
        foreach ($batidas as $hora) {
            $hora = trim((string)$hora);
            if ($this->isHoraValida($hora) && $hora !== self::PLACEHOLDER) {
                $validas[] = $hora;
            }
        }

        sort($validas, SORT_STRING);
        return array_values($validas);
    }

    private function contarBatidasEfetivas(array $batidas): int
    {
        $total = 0;
        foreach ($batidas as $hora) {
            if ($this->isEffectiveHora((string)$hora)) {
                $total++;
            }
        }

        return $total;
    }

    private function slotsParaDisplay(array $slots, bool $manual, int $batidasEfetivas): array
    {
        if (!$manual && $batidasEfetivas === 0) {
            return array_fill(0, self::SLOT_COUNT, '');
        }

        return array_map(static function ($hora) use ($manual) {
            $hora = (string)$hora;
            return $manual ? $hora . '*' : $hora;
        }, array_slice($slots, 0, self::SLOT_COUNT));
    }

    private function isEffectiveHora(string $hora): bool
    {
        return $this->isHoraValida($hora) && $hora !== self::PLACEHOLDER;
    }

    private function isHoraValida(string $hora): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora) === 1;
    }

    private function minutesFromHour(string $hora): int
    {
        if (!$this->isHoraValida($hora)) {
            return 0;
        }

        [$h, $m] = array_map('intval', explode(':', $hora));
        return ($h * 60) + $m;
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

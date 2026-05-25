<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;

final class CalendarioService
{
    /** @return array<string,mixed> */
    public function build(array $data, int $month, int $year): array
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $first = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $offset = (int)$first->format('w');

        $marksByDay = [];
        foreach ($data['marcacoes'] ?? [] as $mark) {
            if (empty($mark['data'])) {
                continue;
            }
            $parts = explode('-', $mark['data']);
            if ((int)($parts[0] ?? 0) === $year && (int)($parts[1] ?? 0) === $month) {
                $day = (int)$parts[2];
                $marksByDay[$day] = ($marksByDay[$day] ?? 0) + 1;
            }
        }

        $cadByDay = [];
        foreach ($data['eventosCadastro'] ?? [] as $event) {
            if (empty($event['data'])) {
                continue;
            }
            $parts = explode('-', $event['data']);
            if ((int)($parts[0] ?? 0) === $year && (int)($parts[1] ?? 0) === $month) {
                $day = (int)$parts[2];
                $cadByDay[$day] = ($cadByDay[$day] ?? 0) + 1;
            }
        }

        $weeks = [];
        $week = array_fill(0, 7, null);
        for ($i = 0; $i < $offset; $i++) {
            $week[$i] = null;
        }
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $weekday = (int)(new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day)))->format('w');
            $week[$weekday] = [
                'day' => $day,
                'date' => sprintf('%04d-%02d-%02d', $year, $month, $day),
                'batidas' => $marksByDay[$day] ?? 0,
                'cadastros' => $cadByDay[$day] ?? 0,
            ];
            if ($weekday === 6 || $day === $daysInMonth) {
                $weeks[] = $week;
                $week = array_fill(0, 7, null);
            }
        }

        return ['month' => $month, 'year' => $year, 'weeks' => $weeks];
    }
}

<?php

declare(strict_types=1);

namespace App\Helpers;

use DateTimeImmutable;

final class Format
{
    public static function onlyDigits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string)$value) ?? '';
    }

    public static function dateBr(?string $date): string
    {
        if (!$date) {
            return '--';
        }
        try {
            return (new DateTimeImmutable($date))->format('d/m/Y');
        } catch (\Throwable) {
            return $date;
        }
    }

    public static function dateIso(?string $date): string
    {
        if (!$date) {
            return '';
        }
        $date = trim($date);
        if (preg_match('/^(\d{2})(\d{2})(\d{4})$/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date)) {
            return $date;
        }
        return '';
    }

    public static function time(?string $time): string
    {
        if (!$time) {
            return '--';
        }
        $time = trim($time);
        if (preg_match('/^(\d{2})(\d{2})$/', $time, $m)) {
            return $m[1] . ':' . $m[2];
        }
        if (preg_match('/^(\d{2}:\d{2})/', $time, $m)) {
            return $m[1];
        }
        return $time;
    }

    public static function minutesToHm(?int $minutes): string
    {
        $minutes ??= 0;
        $sign = $minutes < 0 ? '-' : '';
        $minutes = abs($minutes);
        return sprintf('%s%02d:%02d', $sign, intdiv($minutes, 60), $minutes % 60);
    }

    public static function hmToMinutes(?string $hm): int
    {
        if (!$hm) {
            return 0;
        }
        if (!preg_match('/^(\d{1,3}):(\d{2})$/', trim($hm), $m)) {
            return 0;
        }
        return ((int)$m[1] * 60) + (int)$m[2];
    }

    public static function monthName(int $month): string
    {
        return [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
            7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ][$month] ?? (string)$month;
    }

    public static function weekdayName(int $weekday): string
    {
        return ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$weekday] ?? '';
    }

    public static function weekdayShort(int $weekday): string
    {
        return ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB'][$weekday] ?? '';
    }

    public static function validDate(?string $date): bool
    {
        if (!$date) {
            return false;
        }
        try {
            $d = new DateTimeImmutable($date);
            return $d->format('Y-m-d') === $date;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function validTime(?string $time): bool
    {
        return is_string($time) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }

    public static function moneylessSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        return round($bytes / 1024, 2) . ' kb';
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Format;

final class AfdValidator
{
    /** @param array<int,array<string,mixed>> $linhas */
    public function validateNsrSequence(array $linhas): array
    {
        $seen = [];
        $duplicates = [];
        $breaks = [];
        $previous = null;

        foreach ($linhas as $line) {
            $nsr = $line['nsr'] ?? null;
            if (!$nsr || !ctype_digit((string)$nsr)) {
                continue;
            }
            if (($line['tipo'] ?? null) === 'trailer' || (string)$nsr === '999999999') {
                continue;
            }
            $current = (int)$nsr;
            if (isset($seen[$current])) {
                $duplicates[] = ['nsr' => $nsr, 'linha' => $line['linha'] ?? null];
            }
            $seen[$current] = true;

            if ($previous !== null && $current !== $previous + 1) {
                $breaks[] = ['esperado' => str_pad((string)($previous + 1), 9, '0', STR_PAD_LEFT), 'encontrado' => $nsr, 'linha' => $line['linha'] ?? null];
            }
            $previous = $current;
        }

        return ['duplicidades' => $duplicates, 'quebras' => $breaks];
    }

    public function validCpfCnpj(?string $value): bool
    {
        $digits = Format::onlyDigits($value);
        return in_array(strlen($digits), [11, 14], true) && preg_match('/^(\d)\1+$/', $digits) !== 1;
    }

    public function validPis(?string $value): bool
    {
        $digits = Format::onlyDigits($value);
        return in_array(strlen($digits), [11, 12], true);
    }

    public function duplicateHashExists(string $hash, callable $exists): bool
    {
        return (bool)$exists($hash);
    }
}

<?php
namespace App\Services;

/**
 * Persistência simples de ajustes manuais de marcação por colaborador/dia.
 *
 * O AFD importado permanece intacto. Quando existe ajuste manual para uma data,
 * ele substitui as batidas do AFD somente no cálculo do espelho e da exportação.
 */
class MarcacaoManualService
{
    private const SLOT_COUNT = 4;
    private const PLACEHOLDER = '00:00';

    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?: __DIR__ . '/../../storage/config/marcacoes_manuais.json';
    }

    public function all(): array
    {
        if (!is_file($this->file)) {
            return [];
        }

        $json = file_get_contents($this->file);
        $data = json_decode($json ?: '{}', true);

        return is_array($data) ? $data : [];
    }

    public function forPis(string $pis): array
    {
        $pis = trim($pis);
        $all = $this->all();
        $items = $all[$pis] ?? [];

        return is_array($items) ? $items : [];
    }

    public function getDay(string $pis, string $data): ?array
    {
        if (!$this->isIsoDate($data)) {
            return null;
        }

        $items = $this->forPis($pis);
        $day = $items[$data] ?? null;

        return is_array($day) ? $this->normalizeDay($day) : null;
    }

    public function saveDay(string $pis, string $data, array $batidas, string $comentario = ''): ?array
    {
        $pis = trim($pis);
        if ($pis === '' || !$this->isIsoDate($data)) {
            throw new \InvalidArgumentException('PIS/CPF ou data inválidos para ajuste manual.');
        }

        $batidas = $this->normalizeBatidas($batidas);
        $comentario = $this->normalizeComentario($comentario);
        $all = $this->all();

        if (!$this->hasEffectiveBatidas($batidas)) {
            unset($all[$pis][$data]);
            if (empty($all[$pis])) {
                unset($all[$pis]);
            }
            $this->write($all);
            return null;
        }

        $all[$pis][$data] = [
            'batidas' => $batidas,
            'comentario' => $comentario,
            'updated_at' => date('c'),
        ];

        ksort($all[$pis]);
        ksort($all);
        $this->write($all);

        return $all[$pis][$data];
    }

    public function deleteDay(string $pis, string $data): void
    {
        $this->saveDay($pis, $data, []);
    }

    /**
     * Normaliza as marcações manuais em quatro posições fixas:
     * Entrada 1, Saída 1, Entrada 2 e Saída 2.
     *
     * O valor 00:00 é tratado como espaço reservado e não entra nos cálculos.
     * A ordem informada pelo usuário é preservada para permitir corrigir apenas
     * um horário sem deslocar as demais posições.
     */
    public function normalizeBatidas(array $batidas): array
    {
        $slots = [];
        for ($i = 0; $i < self::SLOT_COUNT; $i++) {
            $hora = trim((string)($batidas[$i] ?? ''));

            if ($hora === '') {
                $slots[] = self::PLACEHOLDER;
                continue;
            }

            if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora)) {
                $slots[] = self::PLACEHOLDER;
                continue;
            }

            $slots[] = $hora;
        }

        return $slots;
    }

    public function hasEffectiveBatidas(array $batidas): bool
    {
        foreach ($batidas as $hora) {
            if ($this->isEffectiveHora((string)$hora)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeDay(array $day): array
    {
        return [
            'batidas' => $this->normalizeBatidas($day['batidas'] ?? []),
            'comentario' => $this->normalizeComentario((string)($day['comentario'] ?? '')),
            'updated_at' => (string)($day['updated_at'] ?? ''),
        ];
    }

    private function isEffectiveHora(string $hora): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora) === 1 && $hora !== self::PLACEHOLDER;
    }

    private function normalizeComentario(string $comentario): string
    {
        $comentario = preg_replace('/\s+/u', ' ', trim($comentario)) ?? trim($comentario);
        return function_exists('mb_substr') ? mb_substr($comentario, 0, 180, 'UTF-8') : substr($comentario, 0, 180);
    }

    private function isIsoDate(string $data): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1;
    }

    private function write(array $data): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        file_put_contents(
            $this->file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}

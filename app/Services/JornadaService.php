<?php
namespace App\Services;

/**
 * Stores and retrieves per-employee work schedule settings.
 *
 * The application has no database dependency in this first version, so the
 * configuration is persisted in JSON at storage/config/jornadas.json.
 */
class JornadaService
{
    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?: __DIR__ . '/../../storage/config/jornadas.json';
    }

    public function defaultConfig(): array
    {
        return [
            'semanal_minutos' => 44 * 60,
            'diaria_minutos' => 9 * 60,
            'tolerancia_minutos' => 10,
            'dias_uteis' => [1, 2, 3, 4, 5],
            'custom' => false,
        ];
    }

    public function get(string $pis): array
    {
        $all = $this->all();
        $config = $all[$pis] ?? [];
        return array_merge($this->defaultConfig(), $config);
    }

    public function save(string $pis, array $input): array
    {
        $all = $this->all();
        $dias = $input['dias_uteis'] ?? [1, 2, 3, 4, 5];
        if (!is_array($dias)) {
            $dias = [1, 2, 3, 4, 5];
        }
        $dias = array_map('intval', $dias);
        $dias = array_values(array_unique(array_filter($dias, static fn ($d) => $d >= 1 && $d <= 7)));
        sort($dias);

        $all[$pis] = [
            'semanal_minutos' => self::parseHourToMinutes((string)($input['semanal'] ?? '44:00'), 44 * 60),
            'diaria_minutos' => self::parseHourToMinutes((string)($input['diaria'] ?? '08:00'), 8 * 60),
            'tolerancia_minutos' => max(0, (int)($input['tolerancia'] ?? 10)),
            'dias_uteis' => $dias ?: [1, 2, 3, 4, 5],
            'custom' => true,
        ];

        $this->write($all);
        return $all[$pis];
    }

    public function all(): array
    {
        if (!file_exists($this->file)) {
            return [];
        }

        $json = file_get_contents($this->file);
        $data = json_decode($json ?: '{}', true);
        return is_array($data) ? $data : [];
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

    public static function parseHourToMinutes(string $value, int $default): int
    {
        $value = trim($value);
        if (!preg_match('/^(\d{1,3}):(\d{2})$/', $value, $m)) {
            return $default;
        }

        $hours = (int)$m[1];
        $minutes = (int)$m[2];
        if ($minutes > 59) {
            return $default;
        }

        return ($hours * 60) + $minutes;
    }

    public static function minutesToHour(int $minutes): string
    {
        $minutes = max(0, $minutes);
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return sprintf('%02d:%02d', $h, $m);
    }
}

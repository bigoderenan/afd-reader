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
            'carga_semanal' => '44:00',
            'carga_diaria' => '09:00',
            'carga_sexta' => '08:00',

            'semanal_minutos' => 2640, // 44h
            'diaria_minutos' => 540,   // 9h segunda a quinta
            'sexta_minutos' => 480,    // 8h sexta-feira

            'tolerancia' => 10,
            'tolerancia_minutos' => 10,

            'dias_uteis' => [1, 2, 3, 4, 5],

            'carga_por_dia' => [
                1 => '09:00',
                2 => '09:00',
                3 => '09:00',
                4 => '09:00',
                5 => '08:00',
                6 => '00:00',
                7 => '00:00',
            ],

            'minutos_por_dia' => [
                1 => 540,
                2 => 540,
                3 => 540,
                4 => 540,
                5 => 480,
                6 => 0,
                7 => 0,
            ],
        ];
    }

    public function get(string $pis): array
    {
        $all = $this->all();
        $config = $all[$pis] ?? [];

        return $this->normalize($config);
    }

    /**
     * Normalizes old/new jornada arrays so views and services never fail with
     * Undefined array key warnings. This also keeps Friday as 8h by default.
     */
    public function normalize(array $config): array
    {
        $defaults = $this->defaultConfig();
        $config = array_replace($defaults, $config);

        $config['semanal_minutos'] = (int)($config['semanal_minutos'] ?? self::parseHourToMinutes((string)($config['carga_semanal'] ?? '44:00'), 2640));
        $config['diaria_minutos'] = (int)($config['diaria_minutos'] ?? self::parseHourToMinutes((string)($config['carga_diaria'] ?? '09:00'), 540));
        $config['sexta_minutos'] = (int)($config['sexta_minutos'] ?? self::parseHourToMinutes((string)($config['carga_sexta'] ?? '08:00'), 480));
        $config['tolerancia_minutos'] = max(0, (int)($config['tolerancia_minutos'] ?? $config['tolerancia'] ?? 10));

        if (!isset($config['dias_uteis']) || !is_array($config['dias_uteis'])) {
            $config['dias_uteis'] = $defaults['dias_uteis'];
        }
        $config['dias_uteis'] = array_values(array_unique(array_map('intval', $config['dias_uteis'])));
        $config['dias_uteis'] = array_values(array_filter($config['dias_uteis'], static fn ($d) => $d >= 1 && $d <= 7));
        if (!$config['dias_uteis']) {
            $config['dias_uteis'] = $defaults['dias_uteis'];
        }
        sort($config['dias_uteis']);

        $baseMinutosPorDia = $defaults['minutos_por_dia'];
        if (isset($config['minutos_por_dia']) && is_array($config['minutos_por_dia'])) {
            foreach ($config['minutos_por_dia'] as $dia => $minutos) {
                $dia = (int)$dia;
                if ($dia >= 1 && $dia <= 7) {
                    $baseMinutosPorDia[$dia] = max(0, (int)$minutos);
                }
            }
        } else {
            $baseMinutosPorDia[1] = $config['diaria_minutos'];
            $baseMinutosPorDia[2] = $config['diaria_minutos'];
            $baseMinutosPorDia[3] = $config['diaria_minutos'];
            $baseMinutosPorDia[4] = $config['diaria_minutos'];
            $baseMinutosPorDia[5] = $config['sexta_minutos'];
            $baseMinutosPorDia[6] = 0;
            $baseMinutosPorDia[7] = 0;
        }
        $config['minutos_por_dia'] = $baseMinutosPorDia;

        $cargaPorDia = [];
        foreach ($config['minutos_por_dia'] as $dia => $minutos) {
            $cargaPorDia[(int)$dia] = self::minutesToHour((int)$minutos);
        }
        $config['carga_por_dia'] = $cargaPorDia;

        $config['carga_semanal'] = self::minutesToHour($config['semanal_minutos']);
        $config['carga_diaria'] = self::minutesToHour($config['diaria_minutos']);
        $config['carga_sexta'] = self::minutesToHour($config['sexta_minutos']);
        $config['tolerancia'] = $config['tolerancia_minutos'];

        return $config;
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

        $diariaMinutos = self::parseHourToMinutes((string)($input['diaria'] ?? '09:00'), 540);
        $sextaMinutos = self::parseHourToMinutes((string)($input['sexta'] ?? '08:00'), 480);

        $config = [
            'semanal_minutos' => self::parseHourToMinutes((string)($input['semanal'] ?? '44:00'), 44 * 60),
            'diaria_minutos' => $diariaMinutos,
            'sexta_minutos' => $sextaMinutos,
            'tolerancia_minutos' => max(0, (int)($input['tolerancia'] ?? 10)),
            'dias_uteis' => $dias ?: [1, 2, 3, 4, 5],
            'minutos_por_dia' => [
                1 => $diariaMinutos,
                2 => $diariaMinutos,
                3 => $diariaMinutos,
                4 => $diariaMinutos,
                5 => $sextaMinutos,
                6 => 0,
                7 => 0,
            ],
            'custom' => true,
        ];

        $all[$pis] = $this->normalize($config);
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

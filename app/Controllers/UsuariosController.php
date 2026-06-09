<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\JornadaService;
use App\Services\EspelhoPontoService;
use App\Services\FolhaPontoExportService;
use App\Services\MarcacaoManualService;

/**
 * Class UsuariosController
 *
 * Lista usuários ativos e excluídos a partir dos eventos de cadastro e das
 * marcações do AFD. Esta versão foi ajustada para o layout 003, onde as
 * marcações e eventos usam data/hora ISO no formato AAAA-MM-DDThh:mm:00-0300.
 */
class UsuariosController extends Controller
{
    public function index(): void
    {
        $cachePath = __DIR__ . '/../../storage/cache/import_data.json';
        if (!file_exists($cachePath)) {
            $_SESSION['upload_message'] = 'Nenhum arquivo foi importado ainda.';
            header('Location: index.php?page=upload');
            exit;
        }

        $parsed = json_decode(file_get_contents($cachePath), true);
        if (!$parsed) {
            $_SESSION['upload_message'] = 'Erro ao ler os dados importados.';
            header('Location: index.php?page=upload');
            exit;
        }

        $usuarios = $parsed['usuarios'] ?? [];
        $jornadaService = new JornadaService();
        $manualService = new MarcacaoManualService();
        [$exportMes, $exportAno] = $this->periodoPadrao($usuarios, $parsed);
        $ativos = [];
        $excluidos = [];

        foreach ($usuarios as $pis => $user) {
            // Ignora chaves vazias/ruins resultantes de linhas inválidas.
            if (trim((string)$pis) === '') {
                continue;
            }

            $marks = $user['marcacoes'] ?? [];
            $manualDates = $this->manualMarkDates((string)$pis, $manualService);
            $numMarks = count($marks) + count($manualDates);
            $firstMark = null;
            $lastMark = null;

            if (count($marks) > 0) {
                usort($marks, static function ($a, $b) {
                    $da = $a['datetime'] ?? (($a['data'] ?? '') . ' ' . ($a['hora'] ?? ''));
                    $db = $b['datetime'] ?? (($b['data'] ?? '') . ' ' . ($b['hora'] ?? ''));
                    return strcmp($da, $db);
                });
                $firstMark = $marks[0];
                $lastMark = $marks[count($marks) - 1];
            }

            $nome = trim((string)($user['nome'] ?? ''));
            $nome = preg_replace('/\s+/', ' ', $nome) ?: (string)$pis;

            $jornada = $jornadaService->get((string)$pis);
            $markDates = $this->mergeDates($this->markDates($marks), $manualDates);
            $usuarioInicio = $this->usuarioStartDate($user);
            $usuarioFim = $this->usuarioEndDate($user);
            $statusPeriodo = $this->statusPeriodo($user, $exportMes, $exportAno, $manualDates);
            $primeiraData = $markDates[0] ?? (string)($firstMark['data'] ?? '');
            $ultimaData = $markDates ? $markDates[count($markDates) - 1] : (string)($lastMark['data'] ?? '');

            $row = [
                'pis'       => (string)$pis,
                'nome'      => $nome,
                'marcacoes' => $numMarks,
                'primeira'  => $primeiraData !== '' ? $this->formatDateOnly($primeiraData) : '-',
                'ultima'    => $ultimaData !== '' ? $this->formatDateOnly($ultimaData) : '-',
                'cargaHoraria' => JornadaService::minutesToHour((int)($jornada['semanal_minutos'] ?? 2640)) . (!empty($jornada['custom']) ? '' : '*'),
                'markDates' => implode(',', $markDates),
                'usuarioInicio' => $usuarioInicio ?? '',
                'usuarioFim' => $usuarioFim ?? '',
                'statusCodigo' => $statusPeriodo['codigo'],
                'statusLabel' => $statusPeriodo['label'],
                'statusClass' => $statusPeriodo['class'],
            ];

            if (!empty($user['ativo'])) {
                $ativos[] = $row;
            } else {
                $excluidos[] = $row;
            }
        }

        // Ordena como no print de referência: lista alfabética por nome.
        usort($ativos, static function ($a, $b) {
            return strcasecmp($a['nome'], $b['nome']);
        });
        usort($excluidos, static function ($a, $b) {
            return strcasecmp($a['nome'], $b['nome']);
        });

        $this->render('usuarios', [
            'ativos'    => $ativos,
            'excluidos' => $excluidos,
            'exportMes' => $exportMes,
            'exportAno' => $exportAno,
            'exportColumns' => FolhaPontoExportService::availableColumns(),
        ]);
    }

    private function periodoPadrao(array $usuarios, array $parsed): array
    {
        $ultima = '';

        foreach ($usuarios as $usuario) {
            foreach (($usuario['marcacoes'] ?? []) as $m) {
                $data = (string)($m['data'] ?? '');
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) && $data > $ultima) {
                    $ultima = $data;
                }
            }
        }

        if ($ultima === '') {
            $ultima = (string)($parsed['arquivo']['dataUltimoNsr'] ?? $parsed['empresa']['dataFim'] ?? '');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $ultima)) {
            return [(int)substr($ultima, 5, 2), (int)substr($ultima, 0, 4)];
        }

        return [(int)date('m'), (int)date('Y')];
    }

    private function markDates(array $marks): array
    {
        $datas = [];
        foreach ($marks as $mark) {
            $data = (string)($mark['data'] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                $datas[$data] = true;
            }
        }

        $datas = array_keys($datas);
        sort($datas);
        return $datas;
    }


    private function manualMarkDates(string $pis, MarcacaoManualService $service): array
    {
        $datas = [];
        foreach ($service->forPis($pis) as $data => $ajuste) {
            $batidas = is_array($ajuste) ? ($ajuste['batidas'] ?? []) : [];
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$data) && is_array($batidas) && count($batidas) > 0) {
                $datas[] = (string)$data;
            }
        }

        sort($datas);
        return $datas;
    }

    private function mergeDates(array ...$dateGroups): array
    {
        $datas = [];
        foreach ($dateGroups as $group) {
            foreach ($group as $data) {
                $data = (string)$data;
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                    $datas[$data] = true;
                }
            }
        }

        $datas = array_keys($datas);
        sort($datas);
        return $datas;
    }

    private function usuarioStartDate(array $usuario): ?string
    {
        $datas = [];

        foreach (($usuario['eventos'] ?? []) as $evento) {
            $operacao = (string)($evento['operacao'] ?? '');
            $data = (string)($evento['data'] ?? '');
            if ($operacao === 'I' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                $datas[] = $data;
            }
        }

        foreach (($usuario['marcacoes'] ?? []) as $mark) {
            $data = (string)($mark['data'] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                $datas[] = $data;
            }
        }

        if (!$datas) {
            return null;
        }

        sort($datas);
        return $datas[0];
    }

    private function usuarioEndDate(array $usuario): ?string
    {
        $eventos = $usuario['eventos'] ?? [];
        if (!$eventos) {
            return null;
        }

        usort($eventos, static function ($a, $b) {
            $da = (string)($a['datetime'] ?? (($a['data'] ?? '') . ' ' . ($a['hora'] ?? '')));
            $db = (string)($b['datetime'] ?? (($b['data'] ?? '') . ' ' . ($b['hora'] ?? '')));
            return strcmp($da, $db);
        });

        $ultimo = $eventos[count($eventos) - 1];
        $data = (string)($ultimo['data'] ?? '');

        return (($ultimo['operacao'] ?? '') === 'E' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) ? $data : null;
    }

    private function statusPeriodo(array $usuario, int $mes, int $ano, array $manualDates = []): array
    {
        $periodStart = sprintf('%04d-%02d-01', $ano, $mes);
        $periodEnd = date('Y-m-t', strtotime($periodStart));
        $usuarioStart = $this->usuarioStartDate($usuario);
        $usuarioEnd = $this->usuarioEndDate($usuario);

        if ($usuarioStart !== null && $usuarioStart > $periodEnd) {
            return ['codigo' => 'incluido_apos', 'label' => 'Incluído após o período', 'class' => 'status-muted'];
        }

        if ($usuarioEnd !== null && $usuarioEnd < $periodStart) {
            return ['codigo' => 'excluido_antes', 'label' => 'Excluído antes do período', 'class' => 'status-muted'];
        }

        foreach (($usuario['marcacoes'] ?? []) as $mark) {
            $data = (string)($mark['data'] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) && $data >= $periodStart && $data <= $periodEnd) {
                return ['codigo' => 'com_registro', 'label' => 'Com registro no período', 'class' => 'status-ok'];
            }
        }

        foreach ($manualDates as $data) {
            $data = (string)$data;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) && $data >= $periodStart && $data <= $periodEnd) {
                return ['codigo' => 'com_registro', 'label' => 'Com registro no período', 'class' => 'status-ok'];
            }
        }

        return ['codigo' => 'sem_registro', 'label' => 'Sem registro no período', 'class' => 'status-warning'];
    }

    private function formatDateOnly(string $date): string
    {
        if ($date === '') {
            return '-';
        }

        // Layout 003 já vem como AAAA-MM-DD.
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return date('d/m/Y', strtotime($date));
        }

        // Compatibilidade com layout antigo DDMMYYYY.
        if (preg_match('/^\d{8}$/', $date)) {
            $dd = substr($date, 0, 2);
            $mm = substr($date, 2, 2);
            $yyyy = substr($date, 4, 4);
            return sprintf('%s/%s/%s', $dd, $mm, $yyyy);
        }

        return $date;
    }
}

<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\JornadaService;
use App\Services\EspelhoPontoService;
use App\Services\FolhaPontoExportService;

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
        [$exportMes, $exportAno] = $this->periodoPadrao($usuarios, $parsed);
        $ativos = [];
        $excluidos = [];

        foreach ($usuarios as $pis => $user) {
            // Ignora chaves vazias/ruins resultantes de linhas inválidas.
            if (trim((string)$pis) === '') {
                continue;
            }

            $marks = $user['marcacoes'] ?? [];
            $numMarks = count($marks);
            $firstMark = null;
            $lastMark = null;

            if ($numMarks > 0) {
                usort($marks, static function ($a, $b) {
                    $da = $a['datetime'] ?? (($a['data'] ?? '') . ' ' . ($a['hora'] ?? ''));
                    $db = $b['datetime'] ?? (($b['data'] ?? '') . ' ' . ($b['hora'] ?? ''));
                    return strcmp($da, $db);
                });
                $firstMark = $marks[0];
                $lastMark = $marks[$numMarks - 1];
            }

            $nome = trim((string)($user['nome'] ?? ''));
            $nome = preg_replace('/\s+/', ' ', $nome) ?: (string)$pis;

            $jornada = $jornadaService->get((string)$pis);
            $row = [
                'pis'       => (string)$pis,
                'nome'      => $nome,
                'marcacoes' => $numMarks,
                'primeira'  => $firstMark ? $this->formatDateOnly($firstMark['data'] ?? '') : '-',
                'ultima'    => $lastMark ? $this->formatDateOnly($lastMark['data'] ?? '') : '-',
                'cargaHoraria' => JornadaService::minutesToHour((int)($jornada['semanal_minutos'] ?? 2640)) . (!empty($jornada['custom']) ? '' : '*'),
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

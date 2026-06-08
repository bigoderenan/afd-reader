<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\EspelhoPontoService;
use App\Services\JornadaService;

class EspelhoController extends Controller
{
    public function index(): void
    {
        $parsed = $this->loadParsed();
        $pis = trim((string)($_GET['pis'] ?? ''));
        if ($pis === '' || !isset($parsed['usuarios'][$pis])) {
            $_SESSION['upload_message'] = 'Funcionário não encontrado para gerar o espelho.';
            header('Location: index.php?page=usuarios');
            exit;
        }

        $service = new EspelhoPontoService();
        [$defaultMes, $defaultAno] = $service->periodoPadrao($parsed['usuarios'][$pis]);
        $mes = max(1, min(12, (int)($_GET['mes'] ?? $defaultMes)));
        $ano = (int)($_GET['ano'] ?? $defaultAno);
        if ($ano < 2000 || $ano > 2100) {
            $ano = $defaultAno;
        }

        $jornadaService = new JornadaService();
        $jornada = $jornadaService->get($pis);
        $espelho = $service->gerar($parsed, $pis, $mes, $ano, $jornada);

        $this->render('espelho', [
            'pis' => $pis,
            'mes' => $mes,
            'ano' => $ano,
            'jornada' => $jornada,
            'espelho' => $espelho,
            'editar' => isset($_GET['editar']),
            'message' => $_SESSION['jornada_message'] ?? null,
        ]);
        unset($_SESSION['jornada_message']);
    }

    public function salvarJornada(): void
    {
        $parsed = $this->loadParsed();
        $pis = trim((string)($_POST['pis'] ?? ''));
        if ($pis === '' || !isset($parsed['usuarios'][$pis])) {
            $_SESSION['upload_message'] = 'Funcionário não encontrado para alterar carga horária.';
            header('Location: index.php?page=usuarios');
            exit;
        }

        $jornadaService = new JornadaService();
        $jornadaService->save($pis, [
            'semanal' => $_POST['semanal'] ?? '44:00',
            'diaria' => $_POST['diaria'] ?? '09:00',
            'sexta' => $_POST['sexta'] ?? '08:00',
            'tolerancia' => $_POST['tolerancia'] ?? 10,
            'dias_uteis' => $_POST['dias_uteis'] ?? [1, 2, 3, 4, 5],
        ]);

        $_SESSION['jornada_message'] = 'Carga horária atualizada com sucesso.';
        $mes = (int)($_POST['mes'] ?? date('m'));
        $ano = (int)($_POST['ano'] ?? date('Y'));
        header('Location: index.php?page=espelho&pis=' . urlencode($pis) . '&mes=' . $mes . '&ano=' . $ano);
        exit;
    }

    private function loadParsed(): array
    {
        $cachePath = __DIR__ . '/../../storage/cache/import_data.json';
        if (!file_exists($cachePath)) {
            $_SESSION['upload_message'] = 'Nenhum arquivo foi importado ainda.';
            header('Location: index.php?page=upload');
            exit;
        }

        $parsed = json_decode(file_get_contents($cachePath), true);
        if (!is_array($parsed)) {
            $_SESSION['upload_message'] = 'Erro ao ler os dados importados.';
            header('Location: index.php?page=upload');
            exit;
        }

        return $parsed;
    }
}

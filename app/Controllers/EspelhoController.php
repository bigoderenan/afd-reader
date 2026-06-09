<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\EspelhoPontoService;
use App\Services\JornadaService;
use App\Services\MarcacaoManualService;

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
        $editarDia = $this->validDateOrNull((string)($_GET['editar_dia'] ?? ''));
        if ($editarDia !== null && ((int)substr($editarDia, 5, 2) !== $mes || (int)substr($editarDia, 0, 4) !== $ano)) {
            $editarDia = null;
        }

        $this->render('espelho', [
            'pis' => $pis,
            'mes' => $mes,
            'ano' => $ano,
            'jornada' => $jornada,
            'espelho' => $espelho,
            'editar' => isset($_GET['editar']),
            'editarDia' => $editarDia,
            'ajusteManual' => $editarDia ? (new MarcacaoManualService())->getDay($pis, $editarDia) : null,
            'message' => $_SESSION['jornada_message'] ?? null,
        ]);
        unset($_SESSION['jornada_message']);
    }

    public function salvarMarcacaoManual(): void
    {
        $parsed = $this->loadParsed();
        $pis = trim((string)($_POST['pis'] ?? ''));
        if ($pis === '' || !isset($parsed['usuarios'][$pis])) {
            $_SESSION['upload_message'] = 'Funcionário não encontrado para alterar marcação.';
            header('Location: index.php?page=usuarios');
            exit;
        }

        $data = $this->validDateOrNull((string)($_POST['data'] ?? ''));
        $mes = max(1, min(12, (int)($_POST['mes'] ?? date('m'))));
        $ano = (int)($_POST['ano'] ?? date('Y'));
        if ($ano < 2000 || $ano > 2100) {
            $ano = (int)date('Y');
        }

        if ($data === null) {
            $_SESSION['jornada_message'] = 'Data inválida para ajuste manual.';
            header('Location: index.php?page=espelho&pis=' . urlencode($pis) . '&mes=' . $mes . '&ano=' . $ano);
            exit;
        }

        $service = new MarcacaoManualService();
        if (!empty($_POST['limpar_ajuste'])) {
            $service->deleteDay($pis, $data);
            $_SESSION['jornada_message'] = 'Ajuste manual removido. O dia voltou a usar as marcações do AFD.';
        } else {
            $batidas = $_POST['batidas'] ?? [];
            if (!is_array($batidas)) {
                $batidas = [];
            }
            $ajuste = $service->saveDay($pis, $data, $batidas, (string)($_POST['comentario'] ?? ''));
            $_SESSION['jornada_message'] = $ajuste === null
                ? 'Ajuste manual removido. O dia voltou a usar as marcações do AFD.'
                : 'Marcações do dia atualizadas com sucesso.';
        }

        header('Location: index.php?page=espelho&pis=' . urlencode($pis) . '&mes=' . $mes . '&ano=' . $ano . '#dia-' . $data);
        exit;
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

    private function validDateOrNull(string $data): ?string
    {
        $data = trim($data);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return null;
        }

        [$ano, $mes, $dia] = array_map('intval', explode('-', $data));
        return checkdate($mes, $dia, $ano) ? $data : null;
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

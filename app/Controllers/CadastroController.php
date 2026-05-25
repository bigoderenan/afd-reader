<?php
namespace App\Controllers;

use App\Core\Controller;

class CadastroController extends Controller
{
    public function index(): void
    {
        $parsed = $this->loadParsed();
        $pis = trim((string)($_GET['pis'] ?? ''));
        if ($pis === '' || !isset($parsed['usuarios'][$pis])) {
            $_SESSION['upload_message'] = 'Funcionário não encontrado para consultar cadastro.';
            header('Location: index.php?page=usuarios');
            exit;
        }

        $usuario = $parsed['usuarios'][$pis];
        $eventos = $usuario['eventos'] ?? [];
        usort($eventos, static function ($a, $b) {
            return ($a['nsr'] ?? 0) <=> ($b['nsr'] ?? 0);
        });

        $this->render('cadastro', [
            'pis' => $pis,
            'usuario' => $usuario,
            'eventos' => $eventos,
        ]);
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

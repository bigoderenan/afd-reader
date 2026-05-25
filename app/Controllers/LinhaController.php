<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * Class LinhaController
 *
 * Provides a simple listing of all lines imported from the AFD file.
 * Supports optional filtering by NSR, tipo and PIS via GET
 * parameters. More advanced searching and pagination could be
 * implemented, but this basic implementation is sufficient to
 * inspect the raw contents line by line.
 */
class LinhaController extends Controller
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
        $linhas = $parsed['linhas'] ?? [];
        // Apply simple filters
        $queryNsr = trim($_GET['nsr'] ?? '');
        $queryTipo = trim($_GET['tipo'] ?? '');
        $queryPis = trim($_GET['pis'] ?? '');
        if ($queryNsr !== '') {
            $linhas = array_filter($linhas, function ($l) use ($queryNsr) {
                return (string) $l['nsr'] === $queryNsr;
            });
        }
        if ($queryTipo !== '') {
            $linhas = array_filter($linhas, function ($l) use ($queryTipo) {
                return $l['tipo'] === $queryTipo;
            });
        }
        if ($queryPis !== '') {
            // Filter by PIS requires scanning marcacoes
            $linhas = array_filter($linhas, function ($l) use ($queryPis) {
                return strpos($l['conteudo'], $queryPis) !== false;
            });
        }
        // Sort by NSR ascending for display
        usort($linhas, function ($a, $b) {
            return $a['nsr'] <=> $b['nsr'];
        });
        $this->render('linhas', [
            'linhas'   => $linhas,
            'queryNsr' => $queryNsr,
            'queryTipo'=> $queryTipo,
            'queryPis' => $queryPis,
        ]);
    }
}
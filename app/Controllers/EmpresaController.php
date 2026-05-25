<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * Class EmpresaController
 *
 * Displays the employer and equipment details extracted from the
 * imported AFD file. Data is read from the cached JSON produced by
 * the upload process.
 */
class EmpresaController extends Controller
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
        $empresa = $parsed['empresa'] ?? [];
        $this->render('empresa', ['empresa' => $empresa]);
    }
}
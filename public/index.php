<?php
// Front controller for the AFD reader application

declare(strict_types=1);

session_start();

// Attempt to load Composer's autoloader if present
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    // Basic PSR-4 autoloader fallback
    spl_autoload_register(function ($class) {
        if (strpos($class, 'App\\') === 0) {
            $relativeClass = substr($class, 4);
            $path = __DIR__ . '/../app/' . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($path)) {
                require $path;
            }
        }
    });
}

$helpers = __DIR__ . '/../app/Helpers/functions.php';
if (file_exists($helpers)) {
    require_once $helpers;
}

use App\Controllers\UploadController;
use App\Controllers\ArquivoController;
use App\Controllers\EmpresaController;
use App\Controllers\UsuariosController;
use App\Controllers\LinhaController;
use App\Controllers\EspelhoController;
use App\Controllers\CadastroController;
use App\Controllers\FolhaController;

// Determine the requested page
$page = $_GET['page'] ?? 'upload';

// Login removido: todas as telas da aplicação ficam acessíveis diretamente.

switch ($page) {
    case 'login':
    case 'login_auth':
    case 'logout':
        header('Location: index.php?page=upload');
        exit;
    case 'upload':
        (new UploadController())->index();
        break;
    case 'upload_process':
        (new UploadController())->process();
        break;
    case 'arquivo':
        (new ArquivoController())->index();
        break;
    case 'empresa':
        (new EmpresaController())->index();
        break;
    case 'usuarios':
        (new UsuariosController())->index();
        break;
    case 'linhas':
        (new LinhaController())->index();
        break;
    case 'espelho':
        (new EspelhoController())->index();
        break;
    case 'salvar_jornada':
        (new EspelhoController())->salvarJornada();
        break;
    case 'salvar_marcacao_manual':
        (new EspelhoController())->salvarMarcacaoManual();
        break;
    case 'cadastro':
        (new CadastroController())->index();
        break;
    case 'exportar_folha':
        (new FolhaController())->exportar();
        break;
    default:
        // Unknown route
        http_response_code(404);
        echo '<h1>404 - Página não encontrada</h1>';
        exit;
}
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
            $path = __DIR__ . '/../' . str_replace('App\\', 'app/', $class) . '.php';
            if (file_exists($path)) {
                require $path;
            }
        }
    });
}

use App\Controllers\UploadController;
use App\Controllers\ArquivoController;
use App\Controllers\EmpresaController;
use App\Controllers\UsuariosController;
use App\Controllers\LinhaController;
use App\Controllers\LoginController;
use App\Controllers\EspelhoController;
use App\Controllers\CadastroController;

// Determine the requested page
$page = $_GET['page'] ?? 'upload';

// If the user is not authenticated, force login for protected pages
$protected = ['upload', 'upload_process', 'arquivo', 'empresa', 'usuarios', 'linhas', 'espelho', 'salvar_jornada', 'cadastro'];
if (in_array($page, $protected, true) && !isset($_SESSION['user'])) {
    $page = 'login';
}

switch ($page) {
    case 'login':
        (new LoginController())->index();
        break;
    case 'login_auth':
        (new LoginController())->authenticate();
        break;
    case 'logout':
        (new LoginController())->logout();
        break;
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
    case 'cadastro':
        (new CadastroController())->index();
        break;
    default:
        // Unknown route
        http_response_code(404);
        echo '<h1>404 - Página não encontrada</h1>';
        exit;
}
<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\AfdParser;
use App\Helpers\Logger;

/**
 * Class UploadController
 *
 * Presents the upload form and processes uploaded AFD files. Upon
 * successful upload and parsing, the parsed data is cached to disk
 * (storage/cache/import_data.json) and the user is redirected to the
 * arquivo page to view the summary.
 */
class UploadController extends Controller
{
    /**
     * Display the upload form. If a message is stored in the session,
     * it is passed to the view for feedback.
     */
    public function index(): void
    {
        $message = $_SESSION['upload_message'] ?? null;
        unset($_SESSION['upload_message']);
        $this->render('upload', ['message' => $message]);
    }

    /**
     * Handle the upload POST request. Validates file extension and size,
     * stores the file to storage/uploads in a date-based directory,
     * parses it using the AfdParser, caches the result and redirects
     * to the arquivo page. In case of errors, an error message is
     * stored in the session and the user is redirected back to the
     * upload form.
     */
    public function process(): void
    {
        if (!isset($_FILES['afd_file']) || $_FILES['afd_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['upload_message'] = 'Nenhum arquivo enviado ou erro no upload.';
            header('Location: index.php?page=upload');
            exit;
        }

        $file = $_FILES['afd_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['txt'];
        if (!in_array($ext, $allowed, true)) {
            $_SESSION['upload_message'] = 'Apenas arquivos .txt são permitidos.';
            header('Location: index.php?page=upload');
            exit;
        }
        // Limit of 10MB for upload
        if ($file['size'] > 10 * 1024 * 1024) {
            $_SESSION['upload_message'] = 'O arquivo enviado excede o tamanho permitido (10MB).';
            header('Location: index.php?page=upload');
            exit;
        }

        // Prepare directory structure /storage/uploads/yyyy/mm
        $dateDir = date('Y/m');
        $uploadBase = __DIR__ . '/../../storage/uploads/' . $dateDir;
        if (!is_dir($uploadBase)) {
            if (!@mkdir($uploadBase, 0775, true) && !is_dir($uploadBase)) {
                $_SESSION['upload_message'] = 'Não foi possível criar a pasta de uploads.';
                header('Location: index.php?page=upload');
                exit;
            }
        }
        // Sanitize file name and generate unique name
        $safeName = preg_replace('/[^A-Za-z0-9_\.-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $targetName = date('Ymd_His') . '_' . md5((string) random_int(0, PHP_INT_MAX)) . '.' . $ext;
        $targetPath = $uploadBase . '/' . $targetName;
        if (!@move_uploaded_file($file['tmp_name'], $targetPath)) {
            $_SESSION['upload_message'] = 'Falha ao mover o arquivo enviado. Verifique permissões.';
            header('Location: index.php?page=upload');
            exit;
        }

        // Parse the uploaded file
        $parser = new AfdParser();
        try {
            $parsed = $parser->parse($targetPath);
        } catch (\Throwable $e) {
            // Remove uploaded file on failure
            @unlink($targetPath);
            $_SESSION['upload_message'] = 'Erro ao processar o arquivo: ' . $e->getMessage();
            header('Location: index.php?page=upload');
            exit;
        }

        // Store parsed data and file info in cache
        $cacheDir = __DIR__ . '/../../storage/cache';
        if (!is_dir($cacheDir)) {
            if (!@mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
                $_SESSION['upload_message'] = 'Não foi possível criar a pasta de cache.';
                header('Location: index.php?page=upload');
                exit;
            }
        }
        // Clean old cache file
        $cachePath = $cacheDir . '/import_data.json';
        @unlink($cachePath);
        // Append file meta information
        $parsed['arquivo']['nome']  = $file['name'];
        $parsed['arquivo']['caminho'] = $targetPath;
        $parsed['arquivo']['tamanho'] = $file['size'];
        $parsed['arquivo']['hash']    = hash_file('sha256', $targetPath);
        file_put_contents($cachePath, json_encode($parsed));

        // Log the upload event with basic statistics. Count inclusões, alterações
        // e exclusões de usuários para compor o registro. Caso o parser não
        // identifique eventos de cadastro, os contadores permanecem em zero.
        $inclusoes = 0;
        $alteracoes = 0;
        $exclusoes = 0;
        if (isset($parsed['eventosCadastro']) && is_array($parsed['eventosCadastro'])) {
            foreach ($parsed['eventosCadastro'] as $ev) {
                if (($ev['operacao'] ?? '') === 'I') {
                    $inclusoes++;
                } elseif (($ev['operacao'] ?? '') === 'A') {
                    $alteracoes++;
                } elseif (($ev['operacao'] ?? '') === 'E') {
                    $exclusoes++;
                }
            }
        }
        $numLinhas = $parsed['arquivo']['numeroLinhas'] ?? 0;
        $numMarcacoes = isset($parsed['marcacoes']) ? count($parsed['marcacoes']) : 0;
        $usuarioLogado = $_SESSION['user'] ?? 'anonimo';
        $logMessage = sprintf(
            'Arquivo "%s" importado por %s (%d bytes). Linhas: %d, marcações: %d, inclusões: %d, alterações: %d, exclusões: %d.',
            $file['name'],
            $usuarioLogado,
            $file['size'],
            $numLinhas,
            $numMarcacoes,
            $inclusoes,
            $alteracoes,
            $exclusoes
        );
        Logger::log($logMessage);

        $_SESSION['upload_message'] = 'Arquivo importado e processado com sucesso!';
        header('Location: index.php?page=arquivo');
        exit;
    }
}
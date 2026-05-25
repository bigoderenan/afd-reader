<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class ImportSession
{
    private static function cacheDir(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Não foi possível criar a pasta storage/cache. Verifique permissões.');
        }
        if (!is_writable($dir)) {
            throw new RuntimeException('A pasta storage/cache não tem permissão de escrita para o PHP/Apache.');
        }
        return $dir;
    }

    public static function put(array $data): void
    {
        $sessionId = preg_replace('/[^a-zA-Z0-9]/', '', session_id());
        if ($sessionId === '') {
            throw new RuntimeException('Sessão inválida. Faça login novamente.');
        }

        $file = self::cacheDir() . '/import_' . $sessionId . '.json';
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new RuntimeException('Não foi possível serializar os dados importados.');
        }
        if (@file_put_contents($file, $json, LOCK_EX) === false) {
            throw new RuntimeException('Não foi possível gravar o cache da importação em storage/cache. Verifique permissões.');
        }
        $_SESSION['afd_import_cache'] = $file;
    }

    public static function get(): ?array
    {
        $file = $_SESSION['afd_import_cache'] ?? null;
        if (!is_string($file) || !is_file($file) || !is_readable($file)) {
            return null;
        }
        $json = file_get_contents($file);
        if ($json === false || trim($json) === '') {
            return null;
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    public static function clear(): void
    {
        $file = $_SESSION['afd_import_cache'] ?? null;
        if (is_string($file) && is_file($file)) {
            @unlink($file);
        }
        unset($_SESSION['afd_import_cache']);
    }

    public static function requireData(): array
    {
        $data = self::get();
        if ($data === null) {
            header('Location: ' . url('/'));
            exit;
        }
        return $data;
    }
}

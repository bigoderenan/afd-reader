<?php
namespace App\Controllers;

use App\Services\FolhaPontoExportService;

class FolhaController
{
    public function exportar(): void
    {
        $parsed = $this->loadParsed();
        [$defaultMes, $defaultAno] = $this->periodoPadrao($parsed);

        $request = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

        $mes = max(1, min(12, (int)($request['mes'] ?? $defaultMes)));
        $ano = (int)($request['ano'] ?? $defaultAno);
        if ($ano < 2000 || $ano > 2100) {
            $ano = $defaultAno;
        }

        $selectedPis = $this->normalizeList($request['pis'] ?? []);
        $selectedColumns = $this->normalizeList($request['columns'] ?? []);
        $periodoTipo = (string)($request['periodo_tipo'] ?? 'month');
        $periodoTipo = $periodoTipo === 'custom' ? 'custom' : 'month';
        $dateStart = $periodoTipo === 'custom' ? $this->normalizeDate((string)($request['data_inicio'] ?? '')) : null;
        $dateEnd = $periodoTipo === 'custom' ? $this->normalizeDate((string)($request['data_fim'] ?? '')) : null;
        $semRegistro = (string)($request['sem_registro'] ?? 'skip');
        if (!in_array($semRegistro, ['skip', 'zero', 'falta'], true)) {
            $semRegistro = 'skip';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$selectedPis) {
            $_SESSION['upload_message'] = 'Selecione pelo menos um colaborador para exportar.';
            $this->safeRedirect('index.php?page=usuarios');
        }

        if ($periodoTipo === 'custom' && $dateStart === null && $dateEnd === null) {
            $_SESSION['upload_message'] = 'Selecione a data inicial ou final para usar período personalizado.';
            $this->safeRedirect('index.php?page=usuarios');
        }

        if ($periodoTipo === 'custom' && $dateStart !== null && $dateEnd !== null && $dateStart > $dateEnd) {
            $_SESSION['upload_message'] = 'A data inicial não pode ser maior que a data final.';
            $this->safeRedirect('index.php?page=usuarios');
        }

        $options = [
            'pis' => $selectedPis,
            'columns' => $selectedColumns,
            'period_mode' => $periodoTipo,
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'sem_registro' => $semRegistro,
            // Exportação iniciada pela tela de usuários deve respeitar estritamente
            // somente os colaboradores marcados na tabela.
            'require_selected' => true,
        ];

        $useXlsx = class_exists(\ZipArchive::class);
        $extension = $useXlsx ? 'xlsx' : 'xls';
        $downloadName = sprintf('folha_ponto_%04d_%02d.%s', $ano, $mes, $extension);

        try {
            $dir = $this->resolveExportDirectory();
            $path = $dir . DIRECTORY_SEPARATOR . sprintf(
                'folha_ponto_%04d_%02d_%s_%s.%s',
                $ano,
                $mes,
                date('Ymd_His'),
                bin2hex(random_bytes(3)),
                $extension
            );

            $exporter = new FolhaPontoExportService();
            if ($useXlsx) {
                $exporter->export($parsed, $mes, $ano, $path, $options);
            } else {
                $exporter->exportXlsXml($parsed, $mes, $ano, $path, $options);
            }
        } catch (\Throwable $e) {
            $_SESSION['upload_message'] = 'Erro ao exportar planilha: ' . $e->getMessage();
            $this->safeRedirect('index.php?page=usuarios');
        }

        if (!isset($path) || !is_file($path)) {
            $_SESSION['upload_message'] = 'A planilha não foi gerada.';
            $this->safeRedirect('index.php?page=usuarios');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . ($useXlsx ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'application/vnd.ms-excel'));
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        readfile($path);
        @unlink($path);
        exit;
    }

    private function normalizeList($value): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $items = [];
        foreach ($value as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $items[] = $item;
            }
        }

        return array_values(array_unique($items));
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    private function resolveExportDirectory(): string
    {
        $storageDir = __DIR__ . '/../../storage';
        $preferredDir = $storageDir . '/exports';

        if ($this->ensureWritableDirectory($preferredDir)) {
            return $preferredDir;
        }

        $tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'afd-reader-exports';
        if ($this->ensureWritableDirectory($tmpDir)) {
            return $tmpDir;
        }

        throw new \RuntimeException(
            'Sem permissão para gravar a exportação. Ajuste a permissão da pasta storage/exports ou do diretório temporário do PHP.'
        );
    }

    private function ensureWritableDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return is_dir($dir) && is_writable($dir);
    }

    private function safeRedirect(string $location): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Location: ' . $location);
        exit;
    }

    private function loadParsed(): array
    {
        $cachePath = __DIR__ . '/../../storage/cache/import_data.json';
        if (!file_exists($cachePath)) {
            $_SESSION['upload_message'] = 'Nenhum arquivo foi importado ainda.';
            $this->safeRedirect('index.php?page=upload');
        }

        $parsed = json_decode((string)file_get_contents($cachePath), true);
        if (!is_array($parsed)) {
            $_SESSION['upload_message'] = 'Erro ao ler os dados importados.';
            $this->safeRedirect('index.php?page=upload');
        }

        return $parsed;
    }

    private function periodoPadrao(array $parsed): array
    {
        $ultima = '';

        foreach (($parsed['usuarios'] ?? []) as $usuario) {
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
}

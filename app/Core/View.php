<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /** @param array<string,mixed> $data */
    public static function render(string $view, array $data = [], ?string $layout = 'layout'): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/' . $view . '.php';
        if (!is_file($viewPath)) {
            http_response_code(500);
            echo 'View não encontrada: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutPath = dirname(__DIR__, 2) . '/resources/views/' . $layout . '.php';
        require $layoutPath;
    }
}

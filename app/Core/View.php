<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): string
    {
        $viewFile = APP_PATH . '/Views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === '') {
            echo $content;
            return $content;
        }

        $layoutFile = APP_PATH . '/Views/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Layout not found: {$layout}");
        }

        ob_start();
        require $layoutFile;
        $output = ob_get_clean();
        echo $output;

        return $output;
    }

    public static function partial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require APP_PATH . '/Views/' . $view . '.php';
    }
}

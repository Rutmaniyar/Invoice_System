<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    public static function back(): never
    {
        $fallback = '/';
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? $fallback);
        $refererHost = parse_url($referer, PHP_URL_HOST);
        $currentHost = $_SERVER['HTTP_HOST'] ?? null;
        if ($refererHost && $currentHost && !hash_equals($currentHost, $refererHost)) {
            $referer = $fallback;
        }

        header('Location: ' . $referer);
        exit;
    }

    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_THROW_ON_ERROR);
        exit;
    }
}

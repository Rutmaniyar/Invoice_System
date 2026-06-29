<?php

declare(strict_types=1);

namespace App\Support;

final class Paginator
{
    public static function page(mixed $value): int
    {
        $page = filter_var($value, FILTER_VALIDATE_INT);
        return $page !== false && $page > 0 ? $page : 1;
    }

    public static function perPage(): int
    {
        return 25;
    }

    public static function offset(int $page): int
    {
        return ($page - 1) * self::perPage();
    }

    public static function meta(int $total, int $page): array
    {
        return [
            'total' => $total,
            'page' => $page,
            'per_page' => self::perPage(),
            'pages' => max(1, (int) ceil($total / self::perPage())),
        ];
    }
}

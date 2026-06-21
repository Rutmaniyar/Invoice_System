<?php

declare(strict_types=1);

namespace App\Services;

final class RateLimiter
{
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        $row = app()->db()->fetch(
            'SELECT attempts, available_at FROM rate_limits WHERE limiter_key = ?',
            [$key]
        );

        if (!$row) {
            return false;
        }

        if (strtotime((string) $row['available_at']) <= time()) {
            $this->clear($key);
            return false;
        }

        return (int) $row['attempts'] >= $maxAttempts;
    }

    public function hit(string $key, int $decayMinutes): void
    {
        $availableAt = date('Y-m-d H:i:s', time() + ($decayMinutes * 60));
        app()->db()->execute(
            'INSERT INTO rate_limits (limiter_key, attempts, available_at)
             VALUES (?, 1, ?)
             ON DUPLICATE KEY UPDATE attempts = attempts + 1, available_at = VALUES(available_at), updated_at = NOW()',
            [$key, $availableAt]
        );
    }

    public function clear(string $key): void
    {
        app()->db()->execute('DELETE FROM rate_limits WHERE limiter_key = ?', [$key]);
    }
}

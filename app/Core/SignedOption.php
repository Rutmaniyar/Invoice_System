<?php

declare(strict_types=1);

namespace App\Core;

final class SignedOption
{
    public static function seal(string $scope, string $value): string
    {
        $payload = json_encode(['scope' => $scope, 'value' => $value], JSON_THROW_ON_ERROR);

        if (function_exists('openssl_encrypt')) {
            $iv = random_bytes(12);
            $tag = '';
            $ciphertext = openssl_encrypt($payload, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag, $scope);
            if ($ciphertext !== false) {
                return 'enc.' . self::base64UrlEncode($iv . $tag . $ciphertext);
            }
        }

        $signature = hash_hmac('sha256', $scope . '|' . $value, self::key());
        return 'sig.' . self::base64UrlEncode($value) . '.' . $signature;
    }

    public static function verify(string $scope, mixed $token, array $allowedValues): ?string
    {
        $value = self::open($scope, (string) $token);
        if ($value === null) {
            return null;
        }

        $allowed = array_flip(array_map('strval', $allowedValues));
        return array_key_exists($value, $allowed) ? $value : null;
    }

    public static function displayValue(string $scope, mixed $candidate): ?string
    {
        $candidate = (string) $candidate;
        if ($candidate === '') {
            return '';
        }

        return self::open($scope, $candidate) ?? $candidate;
    }

    private static function open(string $scope, string $token): ?string
    {
        if (str_starts_with($token, 'enc.')) {
            $raw = self::base64UrlDecode(substr($token, 4));
            if ($raw === null || strlen($raw) <= 28 || !function_exists('openssl_decrypt')) {
                return null;
            }

            $iv = substr($raw, 0, 12);
            $tag = substr($raw, 12, 16);
            $ciphertext = substr($raw, 28);
            $payload = openssl_decrypt($ciphertext, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag, $scope);
            if ($payload === false) {
                return null;
            }

            $decoded = json_decode($payload, true);
            if (!is_array($decoded) || ($decoded['scope'] ?? '') !== $scope || !isset($decoded['value'])) {
                return null;
            }

            return (string) $decoded['value'];
        }

        if (str_starts_with($token, 'sig.')) {
            $parts = explode('.', $token, 3);
            if (count($parts) !== 3) {
                return null;
            }

            $value = self::base64UrlDecode($parts[1]);
            if ($value === null) {
                return null;
            }

            $expected = hash_hmac('sha256', $scope . '|' . $value, self::key());
            return hash_equals($expected, $parts[2]) ? $value : null;
        }

        return null;
    }

    private static function key(): string
    {
        $material = (string) config('app.key', '') . '|' . Csrf::token();
        return hash('sha256', $material, true);
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): ?string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }
}

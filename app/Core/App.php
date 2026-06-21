<?php

declare(strict_types=1);

namespace App\Core;

final class App
{
    private static ?self $instance = null;

    private Router $router;
    private ?Database $database = null;

    public function __construct(private array $config)
    {
        self::$instance = $this;
        $this->router = new Router();
    }

    public static function instance(): self
    {
        if (!self::$instance) {
            throw new \RuntimeException('Application has not been bootstrapped.');
        }

        return self::$instance;
    }

    public function bootstrap(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', ($this->config['app']['debug'] ?? false) ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');

        Session::start($this->config);
    }

    public function config(): array
    {
        return $this->config;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function db(): Database
    {
        if (!$this->database) {
            $this->database = new Database($this->config['database']);
        }

        return $this->database;
    }

    public function isInstalled(): bool
    {
        return (bool) ($this->config['installed'] ?? false) && is_file(STORAGE_PATH . '/installed.lock');
    }
}

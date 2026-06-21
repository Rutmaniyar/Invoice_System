<?php

declare(strict_types=1);

use App\Core\App;

define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('DATABASE_PATH', ROOT_PATH . '/database');

$vendorAutoload = ROOT_PATH . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require $vendorAutoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = APP_PATH . '/' . $relative . '.php';
        if (is_file($file)) {
            require $file;
        }
    });

    require APP_PATH . '/Core/helpers.php';
}

$configFile = CONFIG_PATH . '/config.php';
$config = is_file($configFile) ? require $configFile : require CONFIG_PATH . '/config.example.php';

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

$app = new App($config);
$app->bootstrap();

return $app;

<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$failures = 0;

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)
        || str_contains($path, DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR)) {
        continue;
    }

    $command = 'php -l ' . escapeshellarg($path);
    exec($command, $output, $code);
    if ($code !== 0) {
        $failures++;
        fwrite(STDERR, implode(PHP_EOL, $output) . PHP_EOL);
    }
}

if ($failures > 0) {
    exit(1);
}

echo "PHP lint passed." . PHP_EOL;

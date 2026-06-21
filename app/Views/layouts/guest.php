<?php $errors = flash('errors') ?? []; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($title ?? config('app.name', 'LedgerFlow')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e((defined('PUBLIC_URL_PREFIX') ? rtrim(PUBLIC_URL_PREFIX, '/') : '') . '/favicon.svg') ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="app-shell">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-bold focus:text-ink-900 focus:shadow-soft">Skip to main content</a>
    <main id="main" class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-5xl" data-motion="fade-up">
            <div class="mb-8 flex items-center justify-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-600 text-white shadow-soft">
                    <?= icon('spark', 'h-6 w-6') ?>
                </div>
                <div>
                    <p class="text-xl font-black tracking-tight text-ink-900">LedgerFlow</p>
                    <p class="text-sm font-medium text-ink-500">Self-hosted invoicing</p>
                </div>
            </div>

            <?php \App\Core\View::partial('partials/flash'); ?>
            <?= $content ?>
        </div>
    </main>
    <?php \App\Core\View::partial('partials/cookie-banner'); ?>
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>

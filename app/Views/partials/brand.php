<?php
$business = $business ?? [];
$logoPath = trim((string) ($business['logo_path'] ?? ''));
$businessName = trim((string) ($business['business_name'] ?? ''));
$mainText = $businessName !== '' ? $businessName : 'LedgerFlow';
$subText = $businessName !== '' ? 'LedgerFlow' : ($tagline ?? 'Self-hosted invoicing');
$dark = ($variant ?? 'light') === 'dark';
$large = ($size ?? 'md') === 'lg';
?>
<span class="flex min-w-0 items-center gap-3">
    <span class="flex <?= $large ? 'h-11 w-11' : 'h-10 w-10' ?> shrink-0 items-center justify-center overflow-hidden rounded-lg bg-brand-500 text-white shadow-sm">
        <?php if ($logoPath !== ''): ?>
            <img src="<?= e(upload_url($logoPath)) ?>" alt="<?= e($mainText) ?>" class="h-full w-full object-cover">
        <?php else: ?>
            <?= icon('spark', $large ? 'h-6 w-6' : 'h-5 w-5') ?>
        <?php endif; ?>
    </span>
    <span class="min-w-0">
        <span class="block <?= $large ? 'text-xl' : 'text-lg' ?> font-black leading-tight <?= $dark ? 'text-white' : 'text-ink-900' ?> truncate"><?= e($mainText) ?></span>
        <span class="block max-w-44 truncate text-xs font-semibold <?= $dark ? 'text-ink-300' : 'text-ink-500' ?>"><?= e($subText) ?></span>
    </span>
</span>

<?php
$links = [
    ['/dashboard', 'dashboard', 'Dashboard'],
    ['/clients', 'clients', 'Clients'],
    ['/quotes', 'quotes', 'Quotes'],
    ['/invoices', 'invoices', 'Invoices'],
    ['/recurring', 'invoices', 'Recurring'],
    ['/expenses', 'expenses', 'Expenses'],
    ['/vendors', 'vendors', 'Vendors'],
    ['/reports', 'reports', 'Reports'],
    ['/settings', 'settings', 'Settings'],
    ['/privacy', 'shield', 'Privacy'],
];
?>
<nav class="space-y-1" aria-label="Primary navigation">
    <?php foreach ($links as [$href, $icon, $label]): ?>
        <?php $active = route_is(trim($href, '/')); ?>
        <a href="<?= e($href) ?>" class="sidebar-link <?= $active ? 'sidebar-link-active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>>
            <?= icon($icon, 'h-5 w-5') ?>
            <span><?= e($label) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

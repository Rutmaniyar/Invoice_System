<?php
$pagination = $pagination ?? ['total' => 0, 'page' => 1, 'pages' => 1];
$query = $query ?? [];
$path = $path ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$page = (int) ($pagination['page'] ?? 1);
$pages = (int) ($pagination['pages'] ?? 1);
$total = (int) ($pagination['total'] ?? 0);
$makeUrl = static function (int $target) use ($path, $query): string {
    $params = array_filter(array_merge($query, ['page' => $target]), static fn ($value): bool => $value !== '' && $value !== null);
    return $path . ($params ? '?' . http_build_query($params) : '');
};
?>
<?php if ($pages > 1): ?>
    <div class="table-footer">
        <span>Page <?= e($page) ?> of <?= e($pages) ?> · <?= e($total) ?> records</span>
        <span class="flex gap-2">
            <?php if ($page > 1): ?><a class="btn-secondary h-8 px-2.5 text-xs" href="<?= e($makeUrl($page - 1)) ?>">Previous</a><?php endif; ?>
            <?php if ($page < $pages): ?><a class="btn-secondary h-8 px-2.5 text-xs" href="<?= e($makeUrl($page + 1)) ?>">Next</a><?php endif; ?>
        </span>
    </div>
<?php endif; ?>

<?php
/** Injects the saved brand/accent colors as CSS custom properties so Settings > Brand color actually re-themes the UI. */
$business = $business ?? [];
$brandHex = (string) ($business['brand_color'] ?? '#0ea394');
$accentHex = (string) ($business['accent_color'] ?? '#8b5cf6');
$brandRamp = color_shades($brandHex);
$accentRamp = color_shades($accentHex);
?>
<style>
:root {
<?php foreach ($brandRamp as $step => $rgb): ?>
  --brand-<?= e($step) ?>: <?= e($rgb) ?>;
<?php endforeach; ?>
<?php foreach ($accentRamp as $step => $rgb): ?>
  --accent-<?= e($step) ?>: <?= e($rgb) ?>;
<?php endforeach; ?>
}
</style>

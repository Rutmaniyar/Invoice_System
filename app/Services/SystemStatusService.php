<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Surfaces the health of optional-but-recommended runtime dependencies (Composer packages, PHP extensions)
 * so a missing one shows up as a visible, actionable signal on the Settings page instead of failing silently.
 */
final class SystemStatusService
{
    /** @return array<int, array{key: string, label: string, ok: bool, message: string, fixable: bool}> */
    public function checks(): array
    {
        $dompdfInstalled = class_exists(\Dompdf\Dompdf::class);
        $gdLoaded = extension_loaded('gd');

        return [
            [
                'key' => 'composer_dependencies',
                'label' => 'PDF rendering quality',
                'ok' => $dompdfInstalled,
                'message' => $dompdfInstalled
                    ? 'Dompdf is installed - invoices and quotes render from the full HTML/CSS template.'
                    : 'Dompdf (a Composer dependency) is not installed, so PDFs fall back to a simplified built-in renderer.',
                'fixable' => true,
            ],
            [
                'key' => 'gd_extension',
                'label' => 'Logo on PDFs',
                'ok' => $gdLoaded,
                'message' => $gdLoaded
                    ? 'The PHP GD extension is enabled - your logo will appear on invoice and quote PDFs.'
                    : 'The PHP GD extension is not enabled on this server, so your logo will be skipped on PDFs even though it shows in the app.',
                'fixable' => false,
            ],
        ];
    }

    public function hasIssues(): bool
    {
        foreach ($this->checks() as $check) {
            if (!$check['ok']) {
                return true;
            }
        }

        return false;
    }
}

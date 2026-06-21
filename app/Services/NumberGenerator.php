<?php

declare(strict_types=1);

namespace App\Services;

final class NumberGenerator
{
    public function nextInvoiceNumber(): string
    {
        return $this->next('invoice_prefix', 'next_invoice_number');
    }

    public function nextQuoteNumber(): string
    {
        return $this->next('quote_prefix', 'next_quote_number');
    }

    private function next(string $prefixKey, string $counterKey): string
    {
        $settings = new SettingsService();
        $prefix = (string) $settings->get($prefixKey, '');
        $counter = (int) $settings->get($counterKey, '1001');
        $settings->set($counterKey, (string) ($counter + 1), true);

        return $prefix . $counter;
    }
}

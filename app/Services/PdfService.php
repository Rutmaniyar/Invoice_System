<?php

declare(strict_types=1);

namespace App\Services;

final class PdfService
{
    public function invoicePdf(int $invoiceId): string
    {
        $invoice = app()->db()->fetch(
            'SELECT invoices.*, clients.name AS client_name, clients.email AS client_email, clients.billing_address
             FROM invoices INNER JOIN clients ON clients.id = invoices.client_id WHERE invoices.id = ?',
            [$invoiceId]
        );
        $items = app()->db()->fetchAll('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order', [$invoiceId]);
        $business = (new SettingsService())->business();

        return $this->basicPdf("Invoice {$invoice['invoice_number']}", $this->documentLines($business, $invoice, $items, 'invoice_number'));
    }

    public function quotePdf(int $quoteId): string
    {
        $quote = app()->db()->fetch(
            'SELECT quotes.*, clients.name AS client_name, clients.email AS client_email, clients.billing_address
             FROM quotes INNER JOIN clients ON clients.id = quotes.client_id WHERE quotes.id = ?',
            [$quoteId]
        );
        $items = app()->db()->fetchAll('SELECT * FROM quote_items WHERE quote_id = ? ORDER BY sort_order', [$quoteId]);
        $business = (new SettingsService())->business();

        return $this->basicPdf("Quote {$quote['quote_number']}", $this->documentLines($business, $quote, $items, 'quote_number'));
    }

    private function documentLines(array $business, array $document, array $items, string $numberKey): array
    {
        $showDiscount = $this->hasAmount($document['discount_total'] ?? 0);
        $showTax = $this->hasAmount($document['tax_total'] ?? 0);
        $lines = [
            $business['business_name'] ?? 'Business',
            trim(($business['address_line1'] ?? '') . ' ' . ($business['city'] ?? '')),
            '',
            strtoupper(str_replace('_', ' ', $numberKey)) . ': ' . $document[$numberKey],
            'Client: ' . $document['client_name'],
            'Date: ' . ($document['issue_date'] ?? ''),
            isset($document['due_date']) ? 'Due: ' . $document['due_date'] : 'Valid until: ' . ($document['valid_until'] ?? ''),
            '',
            'Items',
        ];

        foreach ($items as $item) {
            $parts = [
                (string) $item['description'],
                "{$item['quantity']} x {$item['unit_price']}",
            ];
            if ($showTax) {
                $parts[] = "Tax {$item['tax_rate']}%";
            }
            $parts[] = "Total {$item['line_total']} {$document['currency']}";

            $lines[] = implode(' | ', $parts);
        }

        $lines[] = '';
        $lines[] = 'Subtotal: ' . $document['subtotal'] . ' ' . $document['currency'];
        if ($showDiscount) {
            $lines[] = 'Discount: ' . $document['discount_total'] . ' ' . $document['currency'];
        }
        if ($showTax) {
            $lines[] = 'Tax: ' . $document['tax_total'] . ' ' . $document['currency'];
        }
        $lines[] = 'Total: ' . $document['total'] . ' ' . $document['currency'];

        return $lines;
    }

    private function basicPdf(string $title, array $lines): string
    {
        $objects = [];
        $content = "BT\n/F1 18 Tf\n50 780 Td\n(" . $this->pdfText($title) . ") Tj\n/F1 10 Tf\n0 -28 Td\n";
        foreach ($lines as $line) {
            $content .= '(' . $this->pdfText($line) . ") Tj\n0 -16 Td\n";
        }
        $content .= "ET";

        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n{$content}\nendstream";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $number = $index + 1;
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function pdfText(string $text): string
    {
        $text = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function hasAmount(mixed $amount): bool
    {
        return abs((float) $amount) > 0.00001;
    }
}

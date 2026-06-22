<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Core\SignedOption;
use App\Core\Validator;
use App\Core\View;
use App\Services\InvoiceCalculator;
use App\Services\PdfService;
use App\Support\ReferenceData;

$failures = 0;

function assert_same(mixed $expected, mixed $actual, string $label): void
{
    global $failures;
    if ($expected !== $actual) {
        $failures++;
        fwrite(STDERR, "FAIL {$label}: expected " . var_export($expected, true) . ', got ' . var_export($actual, true) . PHP_EOL);
    }
}

function assert_contains(string $needle, string $haystack, string $label): void
{
    global $failures;
    if (!str_contains($haystack, $needle)) {
        $failures++;
        fwrite(STDERR, "FAIL {$label}: expected content to contain {$needle}" . PHP_EOL);
    }
}

function assert_not_contains(string $needle, string $haystack, string $label): void
{
    global $failures;
    if (str_contains($haystack, $needle)) {
        $failures++;
        fwrite(STDERR, "FAIL {$label}: expected content not to contain {$needle}" . PHP_EOL);
    }
}

$calculator = new InvoiceCalculator();
$result = $calculator->fromRequest([
    'item_description' => ['Design', 'Hosting'],
    'item_quantity' => ['2', '1'],
    'item_unit_price' => ['100', '50'],
    'item_discount_rate' => ['10', '0'],
    'item_tax_rate' => ['20', '0'],
]);

assert_same(250.0, $result['subtotal'], 'subtotal');
assert_same(20.0, $result['discount_total'], 'discount total');
assert_same(36.0, $result['tax_total'], 'tax total');
assert_same(266.0, $result['total'], 'grand total');
assert_same(2, count($result['items']), 'line item count');

$empty = $calculator->fromRequest([
    'item_description' => [''],
    'item_quantity' => ['1'],
    'item_unit_price' => ['100'],
]);
assert_same([], $empty['items'], 'empty lines ignored');

$currencyToken = SignedOption::seal('currency', 'USD');
assert_same('USD', SignedOption::verify('currency', $currencyToken, ReferenceData::currencyCodes(ReferenceData::currencies())), 'signed currency verifies');
assert_same(null, SignedOption::verify('currency', $currencyToken . 'tampered', ReferenceData::currencyCodes(ReferenceData::currencies())), 'tampered currency rejected');
assert_same(null, SignedOption::verify('currency', SignedOption::seal('currency', 'XXX'), ReferenceData::currencyCodes(ReferenceData::currencies())), 'unknown currency rejected');
assert_same('UTC', SignedOption::verify('timezone', SignedOption::seal('timezone', 'UTC'), ReferenceData::timezones()), 'signed timezone verifies');
assert_same('United States', SignedOption::verify('country', SignedOption::seal('country', 'United States'), ReferenceData::countries()), 'signed country verifies');

$valid = (new Validator([
    'date' => '2026-05-25',
    'id' => '12',
    'status' => 'draft',
]))->date('date', 'Date')->integer('id', 'ID')->in('status', ['draft', 'sent'], 'Status');
assert_same(false, $valid->fails(), 'new validator rules accept valid values');

$invalid = (new Validator([
    'date' => '2026-02-31',
    'id' => '12.4',
    'status' => 'paid',
]))->date('date', 'Date')->integer('id', 'ID')->in('status', ['draft', 'sent'], 'Status');
assert_same(true, $invalid->fails(), 'new validator rules reject invalid values');

$pdfReflection = new ReflectionClass(PdfService::class);
$render = $pdfReflection->getMethod('legacyRender');
$render->setAccessible(true);

$baseInvoice = [
    'invoice_number' => 'INV-001',
    'client_name' => 'Client',
    'client_email' => null,
    'billing_address' => null,
    'issue_date' => '2026-05-25',
    'due_date' => '2026-06-25',
    'status' => 'sent',
    'currency' => 'USD',
    'subtotal' => '100.00',
    'discount_total' => '0.00',
    'tax_total' => '0.00',
    'total' => '100.00',
    'amount_paid' => '0.00',
    'balance_due' => '100.00',
    'paid_at' => null,
    'notes' => null,
    'terms' => null,
];

$zeroAdjustmentPdf = $render->invoke(new PdfService(), ['business_name' => 'Acme'], $baseInvoice, [[
    'description' => 'Service',
    'quantity' => '1',
    'unit_price' => '100.00',
    'tax_rate' => '0.00',
    'line_total' => '100.00',
]], [], 'invoice_number', true);
assert_contains('%PDF-1.4', $zeroAdjustmentPdf, 'PDF has a valid header');
assert_not_contains('(Discount)', $zeroAdjustmentPdf, 'PDF omits zero discount row');
assert_not_contains('(Tax)', $zeroAdjustmentPdf, 'PDF omits zero tax row and item tax column');

$nonZeroAdjustmentPdf = $render->invoke(new PdfService(), ['business_name' => 'Acme'], array_merge($baseInvoice, [
    'invoice_number' => 'INV-002',
    'discount_total' => '5.00',
    'tax_total' => '9.50',
    'total' => '104.50',
    'balance_due' => '104.50',
]), [[
    'description' => 'Service',
    'quantity' => '1',
    'unit_price' => '100.00',
    'tax_rate' => '10.00',
    'line_total' => '104.50',
]], [], 'invoice_number', true);
assert_contains('(Discount)', $nonZeroAdjustmentPdf, 'PDF includes non-zero discount row');
assert_contains('(-5.00 USD)', $nonZeroAdjustmentPdf, 'PDF includes formatted discount amount');
assert_contains('(Tax)', $nonZeroAdjustmentPdf, 'PDF includes non-zero tax row');
assert_contains('(9.50 USD)', $nonZeroAdjustmentPdf, 'PDF includes formatted tax amount');
assert_contains('(10%)', $nonZeroAdjustmentPdf, 'PDF includes item tax percentage when document tax is non-zero');

$paidInvoicePdf = $render->invoke(new PdfService(), ['business_name' => 'Acme'], array_merge($baseInvoice, [
    'status' => 'paid',
    'paid_at' => '2026-06-01 10:00:00',
    'amount_paid' => '100.00',
    'balance_due' => '0.00',
]), [[
    'description' => 'Service',
    'quantity' => '1',
    'unit_price' => '100.00',
    'tax_rate' => '0.00',
    'line_total' => '100.00',
]], [[
    'payment_date' => '2026-06-01',
    'method' => 'Bank transfer',
    'reference' => 'TX-1',
    'amount' => '100.00',
]], 'invoice_number', true);
assert_contains('(PAID)', $paidInvoicePdf, 'PDF includes a PAID stamp for paid invoices');
assert_contains('(PAYMENT HISTORY)', $paidInvoicePdf, 'PDF includes payment history for invoices with payments');
assert_contains('(Bank transfer)', $paidInvoicePdf, 'PDF lists each recorded payment method');

$htmlBusiness = ['business_name' => 'Acme', 'brand_color' => '#0ea394', 'default_currency' => 'USD'];
$htmlInvoice = array_merge($baseInvoice, ['status' => 'paid', 'paid_at' => '2026-06-01 10:00:00', 'amount_paid' => '100.00', 'balance_due' => '0.00']);
ob_start();
$html = View::render('pdf/document', [
    'business' => $htmlBusiness,
    'document' => $htmlInvoice,
    'items' => [['description' => 'Service', 'quantity' => '1', 'unit_price' => '100.00', 'tax_rate' => '0.00', 'line_total' => '100.00']],
    'payments' => [['payment_date' => '2026-06-01', 'method' => 'Bank transfer', 'reference' => 'TX-1', 'amount' => '100.00']],
    'numberKey' => 'invoice_number',
    'isInvoice' => true,
    'defaultCurrency' => 'USD',
    'forPdf' => false,
], '');
ob_end_clean();
assert_contains('<!doctype html>', $html, 'HTML template renders a full document');
assert_contains('PAID', $html, 'HTML template shows the PAID stamp for paid invoices');
assert_contains('PAYMENT HISTORY', $html, 'HTML template includes payment history');
assert_not_contains('Billed in', $html, 'HTML template omits the foreign-currency badge when currencies match');

ob_start();
$foreignCurrencyHtml = View::render('pdf/document', [
    'business' => $htmlBusiness,
    'document' => array_merge($baseInvoice, ['currency' => 'GBP']),
    'items' => [['description' => 'Service', 'quantity' => '1', 'unit_price' => '100.00', 'tax_rate' => '0.00', 'line_total' => '100.00']],
    'payments' => [],
    'numberKey' => 'invoice_number',
    'isInvoice' => true,
    'defaultCurrency' => 'USD',
    'forPdf' => false,
], '');
ob_end_clean();
assert_contains('Billed in GBP', $foreignCurrencyHtml, 'HTML template flags an invoice currency different from the business default');

if ($failures > 0) {
    exit(1);
}

echo "All tests passed." . PHP_EOL;

<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Services\InvoiceCalculator;
use App\Services\PdfService;
use App\Core\SignedOption;
use App\Core\Validator;
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
$documentLines = $pdfReflection->getMethod('documentLines');
$documentLines->setAccessible(true);
$zeroAdjustmentLines = implode("\n", $documentLines->invoke(new PdfService(), ['business_name' => 'Acme'], [
    'invoice_number' => 'INV-001',
    'client_name' => 'Client',
    'issue_date' => '2026-05-25',
    'due_date' => '2026-06-25',
    'currency' => 'USD',
    'subtotal' => '100.00',
    'discount_total' => '0.00',
    'tax_total' => '0.00',
    'total' => '100.00',
], [[
    'description' => 'Service',
    'quantity' => '1',
    'unit_price' => '100.00',
    'tax_rate' => '0.00',
    'line_total' => '100.00',
]], 'invoice_number'));
assert_not_contains('Discount:', $zeroAdjustmentLines, 'PDF omits zero discount summary');
assert_not_contains('Tax:', $zeroAdjustmentLines, 'PDF omits zero tax summary and item tax');

$nonZeroAdjustmentLines = implode("\n", $documentLines->invoke(new PdfService(), ['business_name' => 'Acme'], [
    'invoice_number' => 'INV-002',
    'client_name' => 'Client',
    'issue_date' => '2026-05-25',
    'due_date' => '2026-06-25',
    'currency' => 'USD',
    'subtotal' => '100.00',
    'discount_total' => '5.00',
    'tax_total' => '9.50',
    'total' => '104.50',
], [[
    'description' => 'Service',
    'quantity' => '1',
    'unit_price' => '100.00',
    'tax_rate' => '10.00',
    'line_total' => '104.50',
]], 'invoice_number'));
assert_contains('Discount: 5.00 USD', $nonZeroAdjustmentLines, 'PDF includes non-zero discount summary');
assert_contains('Tax: 9.50 USD', $nonZeroAdjustmentLines, 'PDF includes non-zero tax summary');
assert_contains('Tax 10.00%', $nonZeroAdjustmentLines, 'PDF includes item tax when document tax is non-zero');

if ($failures > 0) {
    exit(1);
}

echo "All tests passed." . PHP_EOL;

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\SettingsService;

final class ReportController extends Controller
{
    /**
     * Headline figures only ever total the business's default currency - the business always receives that
     * currency. Any invoice/payment/expense booked in a different currency is broken out separately below
     * rather than being summed into the same number, since blending currencies into one total is meaningless.
     */
    public function index(Request $request): string
    {
        $from = (string) $request->input('from', date('Y-m-01'));
        $to = (string) $request->input('to', date('Y-m-d'));
        $currency = (string) ((new SettingsService())->business()['default_currency'] ?? 'USD');

        $income = app()->db()->fetch(
            'SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE currency = ? AND payment_date BETWEEN ? AND ?',
            [$currency, $from, $to]
        )['total'] ?? 0;
        $expenses = app()->db()->fetch(
            'SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE currency = ? AND expense_date BETWEEN ? AND ?',
            [$currency, $from, $to]
        )['total'] ?? 0;
        $outstanding = app()->db()->fetch(
            "SELECT COALESCE(SUM(balance_due), 0) AS total FROM invoices WHERE currency = ? AND status NOT IN ('paid','void','draft')",
            [$currency]
        )['total'] ?? 0;
        $clientLedger = app()->db()->fetchAll(
            "SELECT clients.name, COALESCE(SUM(invoices.total), 0) AS invoiced, COALESCE(SUM(invoices.amount_paid), 0) AS paid, COALESCE(SUM(invoices.balance_due), 0) AS outstanding
             FROM clients LEFT JOIN invoices ON invoices.client_id = clients.id AND invoices.currency = ?
             WHERE clients.deleted_at IS NULL
             GROUP BY clients.id, clients.name
             ORDER BY outstanding DESC, invoiced DESC
             LIMIT 100",
            [$currency]
        );

        $otherCurrencyIncome = app()->db()->fetchAll(
            'SELECT currency, COALESCE(SUM(amount), 0) AS total FROM payments WHERE currency <> ? AND payment_date BETWEEN ? AND ? GROUP BY currency ORDER BY currency',
            [$currency, $from, $to]
        );
        $otherCurrencyOutstanding = app()->db()->fetchAll(
            "SELECT currency, COALESCE(SUM(balance_due), 0) AS total FROM invoices WHERE currency <> ? AND status NOT IN ('paid','void','draft') GROUP BY currency ORDER BY currency",
            [$currency]
        );
        $otherCurrencyLedger = app()->db()->fetchAll(
            "SELECT clients.name, invoices.currency, COALESCE(SUM(invoices.total), 0) AS invoiced, COALESCE(SUM(invoices.amount_paid), 0) AS paid, COALESCE(SUM(invoices.balance_due), 0) AS outstanding
             FROM invoices INNER JOIN clients ON clients.id = invoices.client_id
             WHERE clients.deleted_at IS NULL AND invoices.currency <> ?
             GROUP BY clients.id, clients.name, invoices.currency
             ORDER BY invoices.currency, outstanding DESC
             LIMIT 100",
            [$currency]
        );

        return $this->view('reports/index', [
            'title' => 'Reports',
            'from' => $from,
            'to' => $to,
            'currency' => $currency,
            'income' => $income,
            'expenses' => $expenses,
            'profit' => (float) $income - (float) $expenses,
            'outstanding' => $outstanding,
            'clientLedger' => $clientLedger,
            'otherCurrencyIncome' => $otherCurrencyIncome,
            'otherCurrencyOutstanding' => $otherCurrencyOutstanding,
            'otherCurrencyLedger' => $otherCurrencyLedger,
        ]);
    }
}

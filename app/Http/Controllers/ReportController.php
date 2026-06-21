<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Request;

final class ReportController extends Controller
{
    public function index(Request $request): string
    {
        $from = (string) $request->input('from', date('Y-m-01'));
        $to = (string) $request->input('to', date('Y-m-d'));

        $income = app()->db()->fetch(
            'SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE payment_date BETWEEN ? AND ?',
            [$from, $to]
        )['total'] ?? 0;
        $expenses = app()->db()->fetch(
            'SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE expense_date BETWEEN ? AND ?',
            [$from, $to]
        )['total'] ?? 0;
        $outstanding = app()->db()->fetch(
            "SELECT COALESCE(SUM(balance_due), 0) AS total FROM invoices WHERE status NOT IN ('paid','void','draft')"
        )['total'] ?? 0;
        $clientLedger = app()->db()->fetchAll(
            "SELECT clients.name, COALESCE(SUM(invoices.total), 0) AS invoiced, COALESCE(SUM(invoices.amount_paid), 0) AS paid, COALESCE(SUM(invoices.balance_due), 0) AS outstanding
             FROM clients LEFT JOIN invoices ON invoices.client_id = clients.id
             WHERE clients.deleted_at IS NULL
             GROUP BY clients.id, clients.name
             ORDER BY outstanding DESC, invoiced DESC
             LIMIT 100"
        );

        return $this->view('reports/index', [
            'title' => 'Reports',
            'from' => $from,
            'to' => $to,
            'income' => $income,
            'expenses' => $expenses,
            'profit' => (float) $income - (float) $expenses,
            'outstanding' => $outstanding,
            'clientLedger' => $clientLedger,
        ]);
    }
}

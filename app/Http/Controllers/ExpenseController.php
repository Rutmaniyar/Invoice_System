<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\SignedOption;
use App\Core\Validator;
use App\Services\AuditLogger;
use App\Services\UploadService;
use App\Support\ReferenceData;

final class ExpenseController extends Controller
{
    public function index(): string
    {
        return $this->view('expenses/index', [
            'title' => 'Expenses',
            'expenses' => app()->db()->fetchAll('SELECT * FROM expenses ORDER BY expense_date DESC, id DESC LIMIT 250'),
            'currencies' => app()->db()->fetchAll('SELECT * FROM currencies WHERE is_active = 1 ORDER BY code'),
        ]);
    }

    public function store(Request $request): never
    {
        $data = $request->all();
        $currencyCodes = ReferenceData::currencyCodes(app()->db()->fetchAll('SELECT * FROM currencies WHERE is_active = 1 ORDER BY code'));
        $currency = SignedOption::verify('currency', $data['currency'] ?? '', $currencyCodes);
        $data['currency'] = $currency ?? '';

        $validator = (new Validator($data))
            ->required('vendor', 'Vendor')
            ->required('category', 'Category')
            ->required('expense_date', 'Expense date')
            ->date('expense_date', 'Expense date')
            ->required('amount', 'Amount')
            ->required('currency', 'Currency')
            ->numeric('amount', 'Amount')
            ->numeric('tax_amount', 'Tax amount')
            ->max('vendor', 190, 'Vendor')
            ->max('category', 120, 'Category');

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        try {
            $receiptPath = (new UploadService())->store($request->file('receipt') ?? [], 'receipts');
        } catch (\Throwable $exception) {
            $this->backWithErrors(['receipt' => $exception->getMessage()], $data);
        }

        $id = app()->db()->insert(
            'INSERT INTO expenses (vendor, category, expense_date, amount, tax_amount, currency, payment_method, receipt_path, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['vendor'],
                $data['category'],
                $data['expense_date'],
                (float) $data['amount'],
                (float) ($data['tax_amount'] ?? 0),
                $data['currency'],
                $data['payment_method'] ?? null,
                $receiptPath,
                $data['notes'] ?? null,
                Auth::id(),
            ]
        );

        AuditLogger::log('expense.created', 'expense', $id);
        Session::flash('success', 'Expense recorded.');
        $this->redirect('/expenses');
    }
}

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
use App\Services\InvoiceCalculator;
use App\Services\InvoiceService;
use App\Services\MailerService;
use App\Services\NumberGenerator;
use App\Services\PdfService;
use App\Services\SettingsService;
use App\Support\ReferenceData;

final class InvoiceController extends Controller
{
    public function index(Request $request): string
    {
        $status = (string) $request->input('status', '');
        $params = [];
        $where = '1 = 1';
        if ($status !== '') {
            $where .= ' AND invoices.status = ?';
            $params[] = $status;
        }

        $invoices = app()->db()->fetchAll(
            "SELECT invoices.*, clients.name AS client_name
             FROM invoices INNER JOIN clients ON clients.id = invoices.client_id
             WHERE {$where}
             ORDER BY invoices.issue_date DESC, invoices.id DESC LIMIT 250",
            $params
        );

        return $this->view('invoices/index', [
            'title' => 'Invoices',
            'invoices' => $invoices,
            'status' => $status,
        ]);
    }

    public function create(): string
    {
        return $this->view('invoices/form', [
            'title' => 'New Invoice',
            'clients' => app()->db()->fetchAll('SELECT id, name, currency FROM clients WHERE deleted_at IS NULL ORDER BY name'),
            'currencies' => app()->db()->fetchAll('SELECT * FROM currencies WHERE is_active = 1 ORDER BY code'),
            'taxes' => app()->db()->fetchAll('SELECT * FROM taxes WHERE is_active = 1 ORDER BY name'),
            'business' => (new SettingsService())->business(),
        ]);
    }

    public function store(Request $request): never
    {
        $data = $request->all();
        $currencyCodes = ReferenceData::currencyCodes(app()->db()->fetchAll('SELECT * FROM currencies WHERE is_active = 1 ORDER BY code'));
        $currency = SignedOption::verify('currency', $data['currency'] ?? '', $currencyCodes);
        $data['currency'] = $currency ?? '';
        $clientSelection = trim((string) ($data['client_id'] ?? ''));
        $createClient = $clientSelection === '__new__' || trim((string) ($data['new_client_name'] ?? '')) !== '';
        $clientId = null;

        $validator = (new Validator($data))
            ->required('issue_date', 'Issue date')
            ->date('issue_date', 'Issue date')
            ->required('due_date', 'Due date')
            ->date('due_date', 'Due date')
            ->required('currency', 'Currency')
            ->in('status', ['draft', 'sent'], 'Status');

        $errors = $validator->errors();

        if ($createClient) {
            $clientValidator = (new Validator($data))
                ->required('new_client_name', 'New client name')
                ->max('new_client_name', 190, 'New client name')
                ->email('new_client_email', 'New client email');
            $errors = array_merge($errors, $clientValidator->errors());
            $data['client_id'] = '__new__';
        } elseif ($clientSelection === '' || filter_var($clientSelection, FILTER_VALIDATE_INT) === false) {
            $errors['client_id'] = 'Choose an existing client or create a new client.';
        } else {
            $client = app()->db()->fetch('SELECT id FROM clients WHERE id = ? AND deleted_at IS NULL', [(int) $clientSelection]);
            if (!$client) {
                $errors['client_id'] = 'Selected client was not found.';
            } else {
                $clientId = (int) $client['id'];
            }
        }

        $calculated = (new InvoiceCalculator())->fromRequest($data);
        if ($calculated['items'] === []) {
            $errors['items'] = 'At least one line item is required.';
        }

        if ($errors !== []) {
            $this->backWithErrors($errors, $data);
        }

        $result = app()->db()->transaction(function () use ($data, $calculated, $createClient, $clientId): array {
            if ($createClient) {
                $clientId = app()->db()->insert(
                    'INSERT INTO clients (type, name, email, billing_address, currency, data_processing_basis)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [
                        'business',
                        trim((string) $data['new_client_name']),
                        trim((string) ($data['new_client_email'] ?? '')) ?: null,
                        trim((string) ($data['new_client_billing_address'] ?? '')) ?: null,
                        $data['currency'],
                        'contract',
                    ]
                );
            }

            $invoiceId = app()->db()->insert(
                'INSERT INTO invoices
                 (client_id, invoice_number, status, issue_date, due_date, currency, subtotal, discount_total, tax_total, total, balance_due, notes, terms, public_token, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $clientId,
                    (new NumberGenerator())->nextInvoiceNumber(),
                    $data['status'] ?? 'draft',
                    $data['issue_date'],
                    $data['due_date'],
                    $data['currency'],
                    $calculated['subtotal'],
                    $calculated['discount_total'],
                    $calculated['tax_total'],
                    $calculated['total'],
                    $calculated['total'],
                    $data['notes'] ?? null,
                    $data['terms'] ?? null,
                    hash('sha256', random_bytes(32)),
                    Auth::id(),
                ]
            );

            foreach ($calculated['items'] as $item) {
                app()->db()->execute(
                    'INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, discount_rate, tax_rate, line_total, sort_order)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [$invoiceId, $item['description'], $item['quantity'], $item['unit_price'], $item['discount_rate'], $item['tax_rate'], $item['line_total'], $item['sort_order']]
                );
            }

            return ['invoice_id' => $invoiceId, 'client_id' => (int) $clientId, 'created_client' => $createClient];
        });

        if ($result['created_client']) {
            AuditLogger::log('client.created_from_invoice', 'client', $result['client_id']);
        }
        $invoiceId = (int) $result['invoice_id'];
        AuditLogger::log('invoice.created', 'invoice', $invoiceId);
        Session::flash('success', 'Invoice created.');
        $this->redirect('/invoices/' . $invoiceId);
    }

    public function show(Request $request, string $id): string
    {
        (new InvoiceService())->refreshStatus((int) $id);
        $invoice = app()->db()->fetch(
            'SELECT invoices.*, clients.name AS client_name, clients.email AS client_email, clients.billing_address
             FROM invoices INNER JOIN clients ON clients.id = invoices.client_id WHERE invoices.id = ?',
            [(int) $id]
        );

        if (!$invoice) {
            http_response_code(404);
            return $this->view('errors/404', ['title' => 'Invoice not found']);
        }

        return $this->view('invoices/show', [
            'title' => $invoice['invoice_number'],
            'invoice' => $invoice,
            'items' => app()->db()->fetchAll('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order', [(int) $id]),
            'payments' => app()->db()->fetchAll('SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC', [(int) $id]),
            'business' => (new SettingsService())->business(),
            'paymentMethods' => preg_split('/\r\n|\r|\n/', (string) (new SettingsService())->get('payment_methods', 'Bank transfer')),
        ]);
    }

    public function recordPayment(Request $request, string $id): never
    {
        $data = $request->all();
        $validator = (new Validator($data))
            ->required('amount', 'Amount')
            ->numeric('amount', 'Amount')
            ->required('payment_date', 'Payment date')
            ->required('method', 'Payment method');

        if ($validator->fails() || (float) ($data['amount'] ?? 0) <= 0) {
            $errors = $validator->errors();
            if ((float) ($data['amount'] ?? 0) <= 0) {
                $errors['amount'] = 'Payment amount must be greater than zero.';
            }
            $this->backWithErrors($errors, $data);
        }

        (new InvoiceService())->recordPayment((int) $id, $data);
        Session::flash('success', 'Payment recorded.');
        $this->redirect('/invoices/' . $id);
    }

    public function pdf(Request $request, string $id): never
    {
        $invoice = app()->db()->fetch('SELECT invoice_number FROM invoices WHERE id = ?', [(int) $id]);
        $pdf = (new PdfService())->invoicePdf((int) $id);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . ($invoice['invoice_number'] ?? 'invoice') . '.pdf"');
        echo $pdf;
        exit;
    }

    public function send(Request $request, string $id): never
    {
        $invoice = app()->db()->fetch(
            'SELECT invoices.*, clients.name AS client_name, clients.email AS client_email
             FROM invoices INNER JOIN clients ON clients.id = invoices.client_id WHERE invoices.id = ?',
            [(int) $id]
        );

        if ($invoice && $invoice['client_email']) {
            [$subject, $body] = (new MailerService())->template('invoice_send', [
                'invoice_number' => $invoice['invoice_number'],
                'invoice_total' => money($invoice['total'], $invoice['currency']),
                'balance_due' => money($invoice['balance_due'], $invoice['currency']),
                'due_date' => $invoice['due_date'],
                'client_name' => $invoice['client_name'],
                'business_name' => (new SettingsService())->business()['business_name'] ?? config('app.name'),
            ]);
            (new MailerService())->send($invoice['client_email'], $subject, $body, [[
                'name' => $invoice['invoice_number'] . '.pdf',
                'mime' => 'application/pdf',
                'content' => (new PdfService())->invoicePdf((int) $id),
            ]]);
            (new InvoiceService())->markSent((int) $id);
        }

        Session::flash('success', 'Invoice email queued through the configured mail transport.');
        $this->redirect('/invoices/' . $id);
    }
}

# LedgerFlow

Production-oriented self-hosted invoicing and small-business management for standard PHP shared hosting.

## Features

- Client/customer management with ledger history and GDPR export/anonymization.
- Quotes, PDF generation, email sending, and quote-to-invoice conversion.
- Invoices with status tracking, overdue detection, partial/full payments, reminders-ready templates, and payment history.
- Tax/VAT/GST rates, discounts, due dates, notes, terms, multi-currency metadata.
- Expenses, income/outstanding reports, dashboard analytics, and audit logs.
- Business branding, logo upload, invoice/email templates, roles, permissions, and privacy request tracking.
- Browser installer with PHP extension, database, and writable directory checks.

## Shared Hosting

No Docker and no production Node.js runtime are required. Upload the ZIP to a PHP/MySQL host and visit `/install`.

See:

- `docs/DEPLOYMENT.md`
- `docs/ARCHITECTURE.md`
- `docs/SECURITY.md`
- `docs/GDPR.md`

## Development

```bash
npm install
npm run build:css
composer dump-autoload
composer test
```

The app is intentionally dependency-light. Composer packages are optional and not required for a basic ZIP deployment.
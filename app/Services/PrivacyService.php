<?php

declare(strict_types=1);

namespace App\Services;

final class PrivacyService
{
    public function exportClient(int $clientId): array
    {
        $client = app()->db()->fetch('SELECT * FROM clients WHERE id = ?', [$clientId]);
        if (!$client) {
            throw new \RuntimeException('Client not found.');
        }

        return [
            'client' => $client,
            'quotes' => app()->db()->fetchAll('SELECT * FROM quotes WHERE client_id = ?', [$clientId]),
            'invoices' => app()->db()->fetchAll('SELECT * FROM invoices WHERE client_id = ?', [$clientId]),
            'payments' => app()->db()->fetchAll(
                'SELECT payments.* FROM payments INNER JOIN invoices ON invoices.id = payments.invoice_id WHERE invoices.client_id = ?',
                [$clientId]
            ),
        ];
    }

    public function anonymizeClient(int $clientId): void
    {
        app()->db()->execute(
            "UPDATE clients
             SET name = CONCAT('Deleted client #', id), contact_name = NULL, email = NULL, phone = NULL, website = NULL,
                 billing_address = NULL, shipping_address = NULL, tax_number = NULL, notes = NULL, deleted_at = NOW(), updated_at = NOW()
             WHERE id = ?",
            [$clientId]
        );

        AuditLogger::log('privacy.client_anonymized', 'client', $clientId);
    }
}

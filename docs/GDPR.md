# GDPR-Conscious Data Handling

This application stores client contact information, invoice/quote/payment records, user accounts, audit logs, and data subject request records.

## Legal Bases

Client records include `data_processing_basis` with common options:

- `contract`
- `legal_obligation`
- `legitimate_interest`
- `consent`

Financial records are generally retained for contract performance and legal/accounting obligations. Client anonymization preserves financial totals while removing direct contact identifiers.

## Data Subject Rights

Implemented support:

- Export client data from the client profile.
- Anonymize client personal data from the client profile.
- Log and track data subject requests under Privacy with a 30-day deadline.
- Record request status, verification notes, response notes, and handler.

## Retention

The `data_retention_years` setting is seeded to seven years and can be changed in Settings. Operators should align this with local tax and bookkeeping requirements.

## Privacy Policy

Settings include a privacy policy support text field. Operators should publish a full policy covering:

- Controller identity and contact information.
- Categories of data processed.
- Purposes and legal bases.
- Retention periods.
- Data subject rights.
- Processor/subprocessor list, including hosting and email providers.
- Security measures and breach contact process.


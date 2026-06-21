# Security Notes

LedgerFlow implements the following controls:

- CSRF tokens on state-changing forms.
- Escaped output through `e()` in PHP views.
- Prepared PDO statements for database queries.
- Password hashing with PHP `password_hash()`.
- Secure session cookie flags: `HttpOnly`, `SameSite=Lax`, and `Secure` when HTTPS is detected.
- Login rate limiting backed by the database.
- Role-based access control with owner, manager, accountant, and viewer roles.
- File upload MIME validation through Fileinfo, random names, size limits, and `.htaccess` script execution blocks.
- Password reset tokens stored as SHA-256 hashes with one-hour expiry and single-use marking.
- Audit logs for authentication and financial/privacy actions.
- Protected `app`, `config`, `database`, `storage`, and dependency directories through `.htaccess`.
- Privacy exports and client anonymization to support GDPR data subject rights.

## Operational Recommendations

- Use HTTPS only.
- Keep PHP and MySQL/MariaDB patched.
- Prefer a document root pointed at `public/`.
- Restrict database user privileges to this application database.
- Back up the database before future migrations.
- Review audit logs after user, payment, invoice, and privacy operations.

## Known Extension Points

- Add Composer packages `dompdf/dompdf` and `phpmailer/phpmailer` for more sophisticated PDFs and SMTP if the host supports Composer.
- Add TOTP verification using the existing `two_factor_secret` and `two_factor_enabled` columns.
- Add payment gateway modules through isolated service classes and audit all webhook handlers.


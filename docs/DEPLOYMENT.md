# Shared Hosting Deployment

## ZIP Upload

1. Build assets before packaging:
   ```bash
   npm install
   npm run build:css
   ```
2. Upload the project ZIP to the hosting account.
3. Prefer setting the domain document root to `public/`.
4. If that is not possible, upload to `public_html`; the root `.htaccess` protects application folders and rewrites to `public/index.php`.
5. Visit `/install`.
6. Complete compatibility checks, database credentials, admin account, and business profile.
7. The installer writes `config/config.php`, runs migrations, seeds defaults, and creates `storage/installed.lock`.

## Server Requirements

- PHP 8.2 or newer
- MySQL 5.7+/MariaDB 10.4+
- Extensions: PDO, PDO MySQL, OpenSSL, Mbstring, Fileinfo, JSON, Zip, Session
- `allow_url_fopen` enabled (used to check for and download updates)
- Apache or LiteSpeed with `.htaccess` support recommended
- Writable: `storage/`, `storage/sessions/`, `storage/logs/`, `storage/backups/`, `public/uploads/`, `config/` during install

## Local PDO MySQL Fix

`PDO MySQL` is the PHP driver that lets PDO connect to MySQL/MariaDB. The installer blocks installation without it because database migrations and every SQL query use PDO prepared statements.

On Windows PHP installs, check the active configuration with:

```powershell
php --ini
php -r "print_r(PDO::getAvailableDrivers());"
```

Then open the loaded `php.ini`, make sure `extension_dir = "ext"` is enabled, uncomment or add:

```ini
extension=pdo_mysql
```

Restart the PHP dev server and refresh `/install`.

## Updating

Settings &rarr; Software updates lets an owner-role account check GitHub for a newer release and apply it in one click:

1. **Check for updates** calls the GitHub releases API for `Rutmaniyar/Invoice_System` and shows the latest version/notes if newer.
2. **Update to vX.Y.Z** downloads that release's `.zip` asset, zips a full backup of the current install to `storage/backups/`, extracts the new files over the application (skipping `config/config.php`, `storage/`, `.env`, `vendor/`, `node_modules/`), and runs any new database migrations.
3. If anything goes wrong, restore from the matching ZIP in `storage/backups/` via the host's file manager.

This requires the app's own files to stay writable by PHP after install, which is in tension with the config-hardening note below — choose based on whether you want self-service updates or a fully locked-down filesystem.

## Post-install Hardening

- Remove write access from `config/` after installation where the host allows it.
- Keep `display_errors` disabled in `config/config.php`.
- Use HTTPS and force it at the hosting control panel.
- Configure SMTP in `config/config.php` or keep the PHP `mail()` fallback if the host supports it.
- Set a hosting cron job for recurring invoices:
  ```text
  https://your-domain.example/recurring/run?token=VALUE_FROM_settings_table
  ```

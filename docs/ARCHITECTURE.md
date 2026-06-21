# LedgerFlow Architecture

LedgerFlow is a PHP 8.2+ shared-hosting monolith optimized for cPanel, DirectAdmin, Apache, LiteSpeed, and standard PHP/MySQL hosting.

## Deployment Model

- `public/` is the preferred document root.
- If the host cannot point the domain to `public/`, upload the ZIP to the web root and use the included root `.htaccess`, which rewrites requests to `public/index.php` and blocks protected directories.
- Runtime requires PHP 8.2+, PDO MySQL, OpenSSL, Mbstring, Fileinfo, JSON, writable `storage/`, and writable `public/uploads/`.
- Node.js is not required in production. Tailwind CSS is compiled ahead of deployment into `public/assets/css/app.css`.

## Layers

- `public/index.php`: front controller and route table.
- `app/Core`: framework primitives, routing, session security, CSRF, auth, validation, database wrapper, view renderer.
- `app/Http/Controllers`: request handling and response orchestration.
- `app/Services`: business services for installation, settings, audit logging, rate limiting, uploads, invoice calculations, payment state, PDF, email, and privacy exports.
- `app/Views`: PHP-rendered HTML with Tailwind utility classes.
- `database/migrations`: MySQL/MariaDB schema migrations.
- `storage`: logs, sessions, cache, and private writable files.

## Key Decisions

- PHP-rendered MVC avoids Node, queues, workers, Redis, Docker, and long-running processes that shared hosts often disallow.
- Prepared PDO statements are used for application SQL.
- Financial state is denormalized on invoices (`amount_paid`, `balance_due`, `status`) and refreshed after payment writes for fast dashboard/report queries.
- PDF generation has a no-dependency fallback to keep ZIP deployments viable. Dompdf can be added later through Composer for richer layouts.
- Cron-compatible recurring invoices are exposed through `/recurring/run?token=...` using an installer-generated private token.


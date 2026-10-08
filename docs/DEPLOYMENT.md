# Render deployment

The repository includes `Dockerfile`, `docker/start.sh`, `docker/apache.conf`, `docker/mariadb.Dockerfile`, and `render.yaml`. PHP runs behind Apache; MariaDB runs as a private service with a persistent disk. Render does not provide native PHP hosting, so use Docker. See the official [Docker guide](https://render.com/docs/docker) and [Blueprint reference](https://render.com/docs/blueprint-spec).

1. Put this project in a private Git repository. Commit `composer.lock`. Do not commit `.env`, `storage`, demo credentials, database dumps, `vendor`, or `node_modules`.
2. Generate a production key locally with `C:\xampp\php\php.exe artisan key:generate --show`. Store it privately; do not rotate it on each deployment.
3. In Render, create a Blueprint from `render.yaml`. It provisions a web service and a private MariaDB service in Singapore. These use paid Starter resources and a database disk; review the resource costs before creating them.
4. Set `APP_KEY`, `APP_URL=https://your-service.onrender.com`, and real SMTP values (`MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`). The sender must be authorized by your mail provider. Port 587 uses STARTTLS when available; a provider requiring implicit TLS can use port 465 and `MAIL_SCHEME=smtps`.
5. Leave `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_DRIVER=database`, and `CACHE_STORE=database`. The Blueprint supplies the private database host and generated database passwords automatically. Keep the database private; the web service connects on port 3306.
6. Deploy the database first, then the web service. The web startup checks `APP_KEY`, caches configuration, runs forward-only migrations, caches routes/views, and starts Apache on Render's `PORT`. If initial startup races database provisioning, redeploy the web service once MariaDB is ready.
7. From the web service's Shell, run `php artisan workshop:create-admin owner@your-domain.com "Owner Name"`. This generates a temporary password once. Sign in and change it immediately. Never run demo seeding in production; the seeder rejects production.
8. Add real services, parts, secretary, mechanic, and purchaser accounts, then receive your opening inventory through a parts order. Do not enter stock quantities directly in the database.
9. Verify `/up`, login, an email reset delivery, owner restrictions, a booking, an inventory receipt, completion, payment, and PDF export on the deployed site. No hosted deployment or external SMTP delivery was performed during the local build.

Only `/public` is web-accessible. HTTPS URLs, secure cookies, production HSTS, restrictive content headers, and generic error pages are configured. Trusting forwarded headers is limited to the production deployment, which must be reachable only through Render's proxy.

Persist `/var/lib/mysql` exactly for the private MariaDB service. Keep database backups outside the service and test restores; do not rely exclusively on a container filesystem. See Render's [database disk guidance](https://render.com/docs/deploy-mysql). Sessions and rate-limit cache are in MariaDB, so web redeploys do not discard them. No uploaded files or local queue worker are required. Logs go to stderr; password-reset email uses SMTP.

The supplied runtime versions deliberately match the requested PHP 8.2.12 and MariaDB 10.4.32. They are historical patch releases. Before exposing real customer data publicly, review upgrading supported security patch versions and run the included tests against the selected versions. Laravel 12's supported PHP range is documented in its [release notes](https://laravel.com/docs/12.x/releases).

For updates, back up the database, deploy committed code, and let normal migrations run. Never use `migrate:fresh` in production. If code rollback is necessary, roll back the image and assess migration compatibility before changing the database.

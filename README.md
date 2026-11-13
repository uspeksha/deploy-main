 Absensi Backend & Admin Panel 

Backend service dan web admin untuk **Aplikasi Absensi (Flutter)**.  
Aplikasi Flutter digunakan oleh **user**, sementara project ini handle **API + Web Admin**.

Siap dipakai buat production, scalable, dan gampang diintegrasi.

 Tech Stack

-  Backend: Laravel
-  Client App: Flutter (consume REST API)
-  Database: MySQL / PostgreSQL
-  Authentication: Sanctum (API Token)
-  Web Admin: Blade + Tailwind / Bootstrap

## Kolaborasi dan Validasi

Jangan commit langsung ke `develop`. Buat branch untuk setiap pekerjaan, lalu ajukan pull request agar perubahan bisa ditinjau sebelum digabung:

```bash
git pull --ff-only origin develop
git switch -c feature/nama-perubahan
# kerjakan dan uji perubahan
git add .
git commit -m "Jelaskan perubahan"
git push -u origin feature/nama-perubahan
```

GitHub Actions menjalankan `npm run build`, `composer test`, `composer format:check`, `composer analyse`, pemeriksaan cache production Laravel, dan build Docker pada pull request menuju `develop`. Di pengaturan repository, aktifkan branch protection untuk `develop`: wajibkan pull request, minimal satu persetujuan, dan status check `validate`; larang force-push dan penghapusan branch. Tanpa branch protection, anggota tim masih bisa melewati pemeriksaan dengan push langsung.

Sebelum meminta review, jalankan `npm ci`, `composer install`, `npm run build`, `composer test`, `composer format:check`, dan `composer analyse` secara lokal. Jangan commit `.env`, `public/hot`, atau perubahan build yang tidak disengaja. Setiap pull request yang memang mengubah tampilan atau perilaku harus menjelaskan perubahan yang diharapkan dan menyertakan tangkapan layar sebelum/sesudah untuk halaman UI terkait. Build dan tes otomatis menangkap banyak regresi, tetapi tidak dapat menjamin semua perubahan visual atau perilaku tetap sama.

## Statistik Dashboard Absensi

Dashboard admin menampilkan jumlah karyawan aktif laki-laki, perempuan, dan yang jenis kelaminnya belum diisi. HRD dapat mengisi data tersebut saat membuat atau mengedit akun karyawan. Ranking bulanan menampilkan maksimal tiga karyawan untuk kategori hadir, sakit, terlambat, dan izin, dihitung dari catatan absensi yang disetujui. Jika jumlahnya seri, urutan nama lalu ID karyawan menjadi pemutus seri yang konsisten.

## Payroll Freelance

Payroll is available to HRD accounts from the **Payroll Freelance** sidebar menu. It uses approved, non-mocked `hadir` attendance records and counts at most one approved check-in per employee per local work date. Absences and unapproved records are not paid.

- Minggu 1 covers the 1st–15th; Minggu 2 covers the 16th through the actual last day of the selected month.
- The daily rate is Rp120.000. A full 30 minutes of stored lateness per work date deducts Rp7.500; other deductions are not added automatically.
- The current attendance system stores lateness against its fixed 08:00 baseline. Payroll snapshots this stored value and labels the baseline; it does not recalculate or change attendance records.
- Each period can be generated only once as a snapshot. Drafts move through **Draft → Menunggu Persetujuan → Disetujui → Dibayarkan**. “Dibayarkan” is a manual confirmation with a required reference, not a bank transfer.
- Unpaid periods can be cancelled with a reason and recreated as a new draft for the same period using the **Buat Payroll Baru** action. The cancelled payroll remains in history, and the recreation is recorded automatically. Paid snapshots remain locked; any post-payment difference must be entered and settled through the manual reconciliation log and does not change the original slip or trigger a payment.
- HRD can review details, export the period to Excel, preview/print a slip, and download its PDF. The Excel export includes bank, account number, and account-holder name from the employee profile; handle the file as confidential financial data. Employees cannot access another person’s slip through this admin feature.

## Production Deployment

The checked-in `.env.example` is for local development. Do not copy its values unchanged to production: it enables debug mode and defaults to SQLite. Configure the production environment outside source control with at least:

- `APP_ENV=production`, `APP_DEBUG=false`, the canonical HTTPS `APP_URL`, and a strong `APP_KEY`. Generate the key once for a new installation and preserve it across releases; changing it can invalidate sessions and data encrypted by Laravel.
- A production database connection and credentials. This application supports MySQL and PostgreSQL; install the matching PHP PDO driver. Do not point production at the development SQLite database.
- Working mail credentials and a sender address on a domain authorized by the mail provider. Employee account activation and password-reset flows depend on mail delivery.
- `SESSION_SECURE_COOKIE=true` when the public site uses HTTPS. Keep the session and cache stores backed by services that are available and migrated in production.
- For Firebase push delivery, keep the service-account JSON outside the repository and image, mount it read-only into both `app` and `queue`, and set `FIREBASE_PROJECT_ID` plus `FIREBASE_CREDENTIALS` on both services. Never put the JSON key in a committed environment file.

Use PHP 8.2 or newer and install the PHP extensions required by `composer.json` and the selected database driver. The PHP runtime serving requests (PHP-FPM/Apache) must have the same required extensions as the CLI used for Artisan commands.

### Release checklist

1. Run the existing CI workflow for the release: it installs Composer and npm dependencies, builds production assets, and runs `composer test`. Do not deploy a release with a failing `validate` check.
2. Install production PHP dependencies and build or publish the frontend assets:

   ```bash
   composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
   npm ci
   npm run build
   ```

   If Node.js is not installed on the application server, build `public/build` in CI and include that directory in the release artifact.
3. Configure and verify the production environment and database target before running any migration. Back up the intended database and check its current migration state:

   ```bash
   php artisan migrate:status
   php artisan migrate --force
   ```

   Run these commands only against the intended production database. Never use `migrate:fresh` or run seeders as a deployment shortcut. The payroll schema is added by `database/migrations/2026_10_23_000000_create_payroll_tables.php`; review pending migrations and their impact before applying them.
4. Ensure `storage/` and `bootstrap/cache/` are writable by the PHP-FPM/web-server account without making them world-writable. Run `php artisan storage:link` if the app serves files from the public disk.
5. Before a production migration, create and verify an encrypted database-only archive on persistent host storage. The deployment keeps the archive and its root-only encryption key under `/home/deploy/app/predeploy-backups`; configure a private off-site S3 backup destination separately for disaster recovery. The production image must include the matching database dump client. Apply the migration with the release image, then build Laravel's production caches and verify that the release responds successfully at `/up`. When a release changes queued-job code, restart both the `app` and `queue` services with the same release image; do not restart unrelated services:

   ```bash
   php artisan optimize
   ```

   If queue workers or scheduled tasks are enabled in the deployment, restart the workers after release and configure the scheduler separately.

### Docker deployment

The repository includes a multi-stage Dockerfile. Build it from the repository root and provide production configuration at runtime; never bake `.env` or credentials into the image:

```bash
docker build -t deploy-main:release .
docker run --detach --name deploy-main \
  --publish 8080:80 \
  --env-file /path/to/production.env \
  --restart unless-stopped \
  deploy-main:release
```

The runtime environment file must set the production values listed above, including a database host reachable from the container. `storage/` is writable by the application user; mount persistent storage if users upload files that must survive container replacement. Run database backup and migrations as a release step against the intended database before switching traffic to the new container. The image serves Laravel's `/up` health endpoint through Apache.

For reverse-proxy deployments, set `TRUSTED_PROXIES` to the proxy IP addresses or CIDRs (comma-separated). Do not use `*`; configure the edge proxy to overwrite forwarded headers and block direct public access to the application origin. Keep `APP_URL` set to the canonical public HTTPS URL; password-reset links use this configured host rather than request headers.

Take and verify backups before schema changes. Rolling back application code does not automatically roll back database migrations; have a tested recovery plan before applying a migration to production.

### Local Docker Compose

For local Docker testing, `compose.yaml` runs the application and MariaDB on a private Compose network. The database has no published host port, and its data is stored in the `mariadb_data` volume. The app is available at `http://127.0.0.1:8081`; this local setup is not a production hosting recommendation.

The Compose app service reads the existing `.env` but overrides `DB_HOST=db`, so the `.env` can continue to use `127.0.0.1` when running `php artisan serve` directly on the laptop. Create the database root-password file before starting Compose:

```bash
cp docker/mariadb.env.example docker/mariadb.env
```

Replace the example root password in `docker/mariadb.env` with a unique random value. Set `DB_CONNECTION=mysql`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in the ignored local `.env`. Never commit either file or share its contents.

To preserve an existing local database, make a backup first and import it into the new Compose database before starting the application. These commands create a dump outside the repository and do not modify the source database:

```bash
mariadb-dump --single-transaction -u YOUR_DB_USERNAME -p followme > /tmp/followme-backup.sql
docker compose up -d db
docker compose exec -T db sh -c 'MYSQL_PWD="$MARIADB_PASSWORD" mariadb -u"$MARIADB_USER" "$MARIADB_DATABASE"' < /tmp/followme-backup.sql
```

Replace `YOUR_DB_USERNAME` with the local database username; `-p` prompts for its password. The import uses the Compose app database credentials from `.env`. Keep the backup private and delete it securely when it is no longer needed. Check that the imported database is the intended one before running any migration.

Then inspect migration status and apply only the migrations required by the release:

```bash
docker compose run --rm app php artisan migrate:status
docker compose run --rm app php artisan migrate --force
docker compose up -d app
```

Do not run `docker compose down -v` if you need to preserve the Compose database volume.

 Arsitektur Singkat

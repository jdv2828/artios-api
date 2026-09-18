# Operations Guide (Local, Stage, Production)

This repo is split into two apps:

- Backend API: `turnero/` (Laravel 11)
- Frontend: `turnero-front/` (Next.js)

The backend exposes the API under `/api` and the frontend consumes it.

## Local Development

### Prerequisites

- PHP 8.3 + Composer
- Node.js 20+ + npm
- MySQL (this project expects a MySQL instance reachable from your machine)

### Backend (Laravel)

From `turnero/`:

1. Install deps:

```bash
composer install
```

2. Configure `.env` (most important values):

```env
APP_ENV=local
APP_DEBUG=true

APP_URL=http://127.0.0.1:8000
FRONTEND_URL=http://localhost:3000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=turnero
DB_USERNAME=root
DB_PASSWORD=root

# Local email: log emails to storage/logs/laravel.log
MAIL_MAILER=log
```

3. Rebuild DB + seed dev data:

```bash
php artisan migrate:fresh --seed
```

4. Run the API server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

5. Run the queue worker (required for reminder emails):

```bash
php artisan queue:work --sleep=1 --tries=1
```

### Frontend (Next.js)

From `turnero-front/`:

1. Install deps:

```bash
npm install
```

2. Configure `.env.local`:

```env
NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api
```

3. Run dev server:

```bash
npm run dev -- --port 3000
```

### Verify Locally

Backend:

```bash
curl -i http://127.0.0.1:8000/api/services/active
```

Frontend:

- Open `http://localhost:3000/empleados/login`

Seeded credentials after `--seed`:

- Admin: `admin@turnero.com` / `secret`
- Employees: created by factory (see `users` table). Password is `password`.

### Where Emails Go In Local

By default `.env` sets `MAIL_MAILER=log`, so emails are written to:

- `turnero/storage/logs/laravel.log`

The email template is:

- `turnero/resources/views/emails/appointment-notification.blade.php`

The mail class is:

- `turnero/app/Infrastructure/Mail/AppointmentNotificationMail.php`

## Stage/Production: Option A (VPS + Nginx + PHP-FPM + systemd)

This is the classic approach: install PHP-FPM + Nginx on a server, run Laravel as a service, and run the queue worker as a systemd service.

### Backend Deploy (Laravel)

1. Upload code to `/var/www/turnero` (git pull or rsync).
2. Install deps (on the server):

```bash
composer install --no-dev --optimize-autoloader
```

3. Create `.env` for the environment.

Minimum recommendations:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...   # generate once and keep it stable

APP_URL=https://api.example.com
FRONTEND_URL=https://app.example.com

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=turnero
DB_USERNAME=turnero
DB_PASSWORD=***

QUEUE_CONNECTION=database
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="Turnero"
```

4. Ensure writable directories:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

5. Run migrations:

```bash
php artisan migrate --force
```

6. Cache (optional but recommended in prod):

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### systemd: Queue Worker

Create `/etc/systemd/system/turnero-queue.service`:

```ini
[Unit]
Description=Turnero Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/turnero
ExecStart=/usr/bin/php artisan queue:work --sleep=1 --tries=3
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Enable:

```bash
systemctl daemon-reload
systemctl enable --now turnero-queue
systemctl status turnero-queue
```

### (Optional) Scheduler

If you rely on `schedule:*`, add cron:

```cron
* * * * * www-data /usr/bin/php /var/www/turnero/artisan schedule:run >> /dev/null 2>&1
```

### Nginx (Backend)

Typical vhost (simplified):

```nginx
server {
  server_name api.example.com;
  root /var/www/turnero/public;

  index index.php;

  location / {
    try_files $uri $uri/ /index.php?$query_string;
  }

  location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
  }
}
```

## Stage/Production: Option B (Docker / docker-compose)

Use this when you want reproducible deployments (good for stage) or you prefer containerizing everything.

### High-level flow

1. Build images for backend and frontend.
2. Run MySQL + backend + queue worker + frontend containers.
3. Run `php artisan migrate --force` as a one-off job/container.

### Example docker-compose skeleton

This is a reference snippet (you can adapt ports, envs and volumes):

```yaml
services:
  db:
    image: mysql:8
    environment:
      MYSQL_DATABASE: turnero
      MYSQL_USER: turnero
      MYSQL_PASSWORD: turnero
      MYSQL_ROOT_PASSWORD: root
    ports:
      - "3306:3306"

  api:
    build: ./turnero
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
      APP_URL: https://api.example.com
      FRONTEND_URL: https://app.example.com
      DB_CONNECTION: mysql
      DB_HOST: db
      DB_PORT: 3306
      DB_DATABASE: turnero
      DB_USERNAME: turnero
      DB_PASSWORD: turnero
      QUEUE_CONNECTION: database
      MAIL_MAILER: smtp
      # ... SMTP vars
    depends_on:
      - db
    ports:
      - "8000:8000"

  queue:
    build: ./turnero
    command: php artisan queue:work --sleep=1 --tries=3
    environment:
      DB_HOST: db
      # same env as api
    depends_on:
      - api

  web:
    build: ./turnero-front
    environment:
      NEXT_PUBLIC_API_URL: https://api.example.com/api
    ports:
      - "3000:3000"
```

### Notes

- You should still run migrations as part of the deploy (one-off container).
- You can keep `MAIL_MAILER=log` in stage if you do not want to deliver emails.
- Put secrets in your orchestrator secrets manager (not in git).

## Email: How To Move From "log" to Real Delivery

Currently, the app sends mails via `Mail::to(...)->send(new AppointmentNotificationMail(...))` in:

- `app/Infrastructure/Listeners/SendAppointmentConfirmationEmail.php`
- `app/Infrastructure/Jobs/SendAppointmentReminder.php`

### Local debugging (recommended)

1. Keep `MAIL_MAILER=log` and inspect `storage/logs/laravel.log`.

Or

2. Use Mailpit (local SMTP catcher):
- Set `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

- Run Mailpit:

```bash
docker run --rm -p 1025:1025 -p 8025:8025 axllent/mailpit
```

- Open Mailpit UI: `http://localhost:8025`

### Production

Set `MAIL_MAILER=smtp` and configure your provider credentials. Common choices:

- Amazon SES
- Postmark
- Mailgun
- SendGrid

Then verify by booking a test appointment and checking that the confirmation email is received.

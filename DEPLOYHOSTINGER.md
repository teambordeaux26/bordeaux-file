# Deploy DMS to Hostinger

Hostinger's built-in Git deploy runs `composer install` on the server. On shared hosting, PHP often disables `proc_open`, which causes Composer to fail. **Use GitHub Actions instead** — it builds on GitHub and uploads a ready-to-run app.

## 1. One-time server setup (hPanel / SSH)

1. **PHP version:** Set to **8.2 or 8.3** (Websites → Manage → PHP Configuration).
2. **Create MySQL database** and note host, database name, user, password.
3. **Upload `.env`** to your project folder on the server (copy from `.env.example` and fill in production values):
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://yourdomain.com`
   - Database credentials
   - Mail settings
   - Turnstile keys
4. **Document root:** Either:
   - Point the domain to the Laravel **`public`** folder, **or**
   - Deploy the full project to `public_html` and keep the root `.htaccess` (routes requests into `public/`).

Typical SSH path:
```
/home/USERNAME/domains/yourdomain.com
```

## 2. GitHub Actions secrets

In GitHub → **Settings → Secrets and variables → Actions**, add:

| Secret | Example |
|--------|---------|
| `HOSTINGER_HOST` | `123.45.67.89` or `ssh.yourdomain.com` |
| `HOSTINGER_USERNAME` | `u123456789` |
| `HOSTINGER_PASSWORD` | Your SSH password |
| `HOSTINGER_PORT` | `65002` (check hPanel → SSH Access) |
| `HOSTINGER_PATH` | `/home/u123456789/domains/yourdomain.com` |

## 3. Disable Hostinger auto Git deploy (important)

In hPanel → **Advanced → Git** → disconnect the repo **or** turn off auto-deploy.

Otherwise Hostinger will still run `composer install` on the server and fail with `proc_open`.

Deployments will run from **GitHub Actions** (`.github/workflows/deploy-hostinger.yml`) on every push to `main`.

## 4. First deploy

1. Push to `main`.
2. Open GitHub → **Actions** → **Deploy to Hostinger** and confirm it succeeds.
3. Visit your domain.

## 5. Manual deploy via SSH (optional)

If you upload files manually (FTP/File Manager):

**On your PC:**
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

Upload everything **except** `node_modules`, `.git`, and `.env`.

**On the server (SSH):**
```bash
cd /home/USERNAME/domains/yourdomain.com
bash scripts/hostinger-post-deploy.sh
```

## 6. Fix proc_open on Hostinger (alternative)

If you prefer Hostinger's Git deploy:

1. hPanel → **Advanced → PHP Configuration**
2. Remove `proc_open` from **disabled functions**
3. Redeploy

Then run on SSH:
```bash
/opt/alt/php83/usr/bin/php /usr/local/bin/composer2 install --no-dev --optimize-autoloader
```

## Troubleshooting

| Issue | Fix |
|-------|-----|
| `proc_open` error | Use GitHub Actions deploy, or enable `proc_open` in PHP settings |
| 500 error | Check `storage/logs/laravel.log`, fix `.env`, run `chmod -R 775 storage bootstrap/cache` |
| Blank page / no CSS | Run `npm run build` locally or via GitHub Actions; ensure `public/build` exists on server |
| Database error | Verify `.env` DB credentials and run `php artisan migrate --force` |

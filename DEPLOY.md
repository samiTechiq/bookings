# Deploying NATU Bookings to a VPS

Quick guide for an Ubuntu/Debian VPS that already has PHP 8.3, Node.js/npm, Nginx and Certbot, using the `deploy` user.

Replace these placeholders throughout:

| Placeholder                            | Example                                          |
| -------------------------------------- | ------------------------------------------------ |
| `bookings.example.com`                 | your domain (DNS A record must point at the VPS) |
| `git@github.com:you/natu-bookings.git` | your private repository                          |
| `CHANGE_ME_STRONG_PASSWORD`            | a strong password for the MySQL app user         |

App directory: `/var/www/natu-bookings`

---

## 1. Push the code to a Git repository (local machine, once)

The project is not yet a Git repository. From the project folder on your Mac:

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin git@github.com:you/natu-bookings.git
git push -u origin main
```

`.env`, `vendor/` and `node_modules/` are already gitignored.

## 2. Server prerequisites (once)

SSH in as `deploy`:

```bash
ssh deploy@your-server-ip
```

Install MySQL, the PHP extensions the app needs (Filament, DomPDF, MySQL) and Composer:

```bash
sudo apt update
sudo apt install -y git unzip mysql-server php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### Create the database

Secure the MySQL install (answer **Y** to removing anonymous users, disallowing remote root login and removing the test database):

```bash
sudo mysql_secure_installation
```

Create the database and a dedicated app user:

```bash
sudo mysql
```

```sql

```

Check the login works: `mysql -u natu -p natu_bookings -e "SELECT 1;"`

> MySQL only listens on `127.0.0.1` by default; keep it that way (don't open port 3306 in the firewall).

### Deploy key

Let the server pull from your private repo with a deploy key:

```bash
ssh-keygen -t ed25519 -C "deploy@natu-bookings" -f ~/.ssh/id_ed25519 -N ""
cat ~/.ssh/id_ed25519.pub
```

Add that public key in GitHub → repo **Settings → Deploy keys** (read-only), then test: `ssh -T git@github.com`.

## 3. Get the code and install

```bash
sudo mkdir -p /var/www/natu-bookings
sudo chown deploy:www-data /var/www/natu-bookings
git clone git@github.com:you/natu-bookings.git /var/www/natu-bookings
cd /var/www/natu-bookings

composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

## 4. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
nano .env
```

Set at least:

```dotenv
APP_NAME="NATU Bookings"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bookings.example.com

LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=natu_bookings
DB_USERNAME=natu
DB_PASSWORD=CHANGE_ME_STRONG_PASSWORD

SESSION_SECURE_COOKIE=true
```

> The timezone (`Africa/Lagos`) is set in `config/app.php`, so the end-of-day report uses Lagos dates.

## 5. Database, permissions and caches

```bash
php artisan migrate --force
php artisan db:seed --force          # creates the roles + the Root Admin account

# PHP-FPM runs as www-data and must be able to write logs, sessions and caches
sudo chown -R deploy:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

php artisan optimize
php artisan filament:optimize
```

## 6. Nginx

```bash
sudo nano /etc/nginx/sites-available/natu-bookings
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name bookings.example.com;
    root /var/www/natu-bookings/public;

    index index.php;
    charset utf-8;
    client_max_body_size 20M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PWA: the service worker and manifest must never be cached stale
    location = /sw.js {
        add_header Cache-Control "no-cache";
        try_files $uri =404;
    }

    location = /manifest.json {
        add_header Cache-Control "no-cache";
        try_files $uri =404;
    }

    location ~* \.(?:css|js|woff2?|png|svg|ico)$ {
        expires 30d;
        access_log off;
        try_files $uri /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable it:

```bash
sudo ln -s /etc/nginx/sites-available/natu-bookings /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

## 7. HTTPS (required for the installable app)

```bash
sudo certbot --nginx -d bookings.example.com --redirect
```

Certbot adds the SSL block and an HTTP→HTTPS redirect, and renews automatically.

## 8. First login

1. Open `https://bookings.example.com` → you are redirected to `/admin`.
2. Log in as **admin@natufood.com** / **password**.
3. **Immediately** go to **Administration → Users**, edit the Root Admin, and set a new password (and email if you like).
4. Create the Marketing Manager accounts from the same screen.

On the manager's phone, open the site and use **Install app** (Android/Chrome) or **Share → Add to Home Screen** (iPhone/Safari).

---

## Redeploying updates

On your Mac: commit and `git push`. Then on the server:

```bash
cd /var/www/natu-bookings
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan filament:optimize
sudo systemctl reload php8.3-fpm
php artisan up
```

> If you change icons or `public/sw.js`, bump `CACHE = 'natu-static-v…'` in `sw.js` so installed phones refresh their cached files.

## Backups

All data lives in the `natu_bookings` MySQL database. Store the credentials in a file only `deploy` can read, so `mysqldump` doesn't need a password on the command line:

```bash
cat > ~/.my.cnf <<'CNF'
[mysqldump]
user=natu
password=CHANGE_ME_STRONG_PASSWORD
CNF
chmod 600 ~/.my.cnf
mkdir -p ~/backups
crontab -e
```

Nightly compressed backup at 02:00, keeping 14 days:

```cron
0 2 * * * mysqldump --single-transaction --no-tablespaces natu_bookings | gzip > /home/deploy/backups/natu-$(date +\%F).sql.gz && find /home/deploy/backups -name 'natu-*.sql.gz' -mtime +14 -delete
```

Restore a backup:

```bash
gunzip < ~/backups/natu-2026-11-02.sql.gz | mysql -u natu -p natu_bookings
```

Copy backups off the server periodically.

## Troubleshooting

| Symptom                                     | Fix                                                                      |
| ------------------------------------------- | ------------------------------------------------------------------------ |
| 500 error / blank page                      | `tail -n 50 storage/logs/laravel.log`                                    |
| `SQLSTATE[HY000] [1045] Access denied`      | check `DB_USERNAME`/`DB_PASSWORD` in `.env`, then `php artisan optimize` |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL isn't running: `sudo systemctl status mysql`                       |
| "Permission denied" writing logs/cache      | re-run the `chown`/`chmod` commands in step 5                            |
| Unstyled admin panel                        | `php artisan filament:assets && php artisan optimize`                    |
| Config/route changes not applied            | `php artisan optimize:clear && php artisan optimize`                     |
| 403 on login for a user                     | the user has no role — assign one in **Users**                           |
| "Install app" not offered                   | site must be on HTTPS (step 7); hard-refresh once                        |

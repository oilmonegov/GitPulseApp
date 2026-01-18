# Deployment Guide

GitPulse can be deployed in multiple environments. Choose the option that best fits your workflow.

---

## Local Development Options

### Laravel Herd (Recommended for macOS)

GitPulse works out of the box with [Laravel Herd](https://herd.laravel.com/).

```bash
# Clone and setup
git clone <repo-url> GitPulseApp
cd GitPulseApp

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run build

# Start development
npm run dev
```

Your site is automatically available at `http://gitpulseapp.test` (or your configured domain).

**Herd-specific commands:**
```bash
# Check PHP version
herd php -v

# Isolate PHP version for this project
herd isolate 8.4

# Secure with HTTPS
herd secure
```

### Laravel Valet (macOS/Linux)

```bash
# Link the project
cd GitPulseApp
valet link

# Secure with HTTPS (optional)
valet secure gitpulseapp

# Access at http://gitpulseapp.test
```

### Built-in PHP Server

```bash
# Quick start with artisan
php artisan serve

# Or use the composer dev script (includes queue, logs, vite)
composer run dev
```

Access at `http://localhost:8000`

### Docker (Optional)

For containerized development or production deployment:

```bash
# Start all services
docker compose up -d

# Run migrations
docker compose exec app php artisan migrate

# Access at http://localhost:8000
```

See `docker-compose.yml` for available services (MySQL, Redis, Mailpit, MinIO).

---

## Production Deployment Options

### Laravel Forge

1. Create a new server on Forge
2. Create a new site pointing to `/public`
3. Connect your repository
4. Configure environment variables
5. Deploy

**Deployment script:**
```bash
cd /home/forge/gitpulse.com
git pull origin main

composer install --no-dev --optimize-autoloader

npm ci
npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan queue:restart
```

### Laravel Vapor (Serverless)

```bash
# Install Vapor CLI
composer require laravel/vapor-cli --dev

# Initialize Vapor
php artisan vapor:init

# Deploy to staging
php artisan vapor:deploy staging

# Deploy to production
php artisan vapor:deploy production
```

### Traditional VPS (Nginx + PHP-FPM)

**Nginx configuration:**
```nginx
server {
    listen 80;
    server_name gitpulse.com;
    root /var/www/gitpulse/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Docker / Kubernetes

Build the production image:
```bash
docker build -t gitpulse:latest --target production .
docker push your-registry/gitpulse:latest
```

---

## Environment Configuration

### Required Environment Variables

```env
# Application
APP_NAME=GitPulse
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gitpulse
DB_USERNAME=gitpulse
DB_PASSWORD=secure-password

# Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# GitHub OAuth
GITHUB_CLIENT_ID=your-client-id
GITHUB_CLIENT_SECRET=your-client-secret
GITHUB_REDIRECT_URI=https://your-domain.com/auth/github/callback

# GitHub Webhooks
GITHUB_WEBHOOK_SECRET=your-webhook-secret

# Sentry (optional but recommended)
SENTRY_LARAVEL_DSN=https://xxx@xxx.ingest.sentry.io/xxx
```

### Database Options

| Environment | Recommended | Configuration |
|-------------|-------------|---------------|
| Local (Herd) | SQLite | `DB_CONNECTION=sqlite` |
| Local (Docker) | MySQL | `DB_CONNECTION=mysql` |
| Production | MySQL/PostgreSQL | `DB_CONNECTION=mysql` |
| Testing | SQLite | `DB_DATABASE=:memory:` |

---

## Post-Deployment Checklist

- [ ] Environment variables configured
- [ ] Application key generated (`php artisan key:generate`)
- [ ] Database migrated (`php artisan migrate --force`)
- [ ] Cache optimized (`php artisan config:cache && php artisan route:cache`)
- [ ] Queue worker running (Supervisor/Horizon)
- [ ] Scheduler configured (cron)
- [ ] SSL certificate installed
- [ ] Health check endpoint responding (`/up`, `/health`)
- [ ] Error tracking configured (Sentry)

---

## Monitoring Endpoints

| Endpoint | Purpose | Response |
|----------|---------|----------|
| `/up` | Basic liveness check | 200 OK |
| `/health` | Detailed health dashboard | HTML |
| `/health/json` | Health check API | JSON |
| `/horizon` | Queue monitoring (if using Horizon) | Dashboard |
| `/docs/api` | API documentation | OpenAPI UI |

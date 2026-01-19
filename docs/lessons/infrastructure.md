---
tags: [docker, ci-cd, sentry, health-checks, pennant, deployment, monitoring, security]
updated: 2026-01-18
---

# Infrastructure Lessons

> **Quick summary for agents**: Docker is optional - app works with Herd, Valet, traditional PHP, or containers. Use Spatie Health for monitoring with conditional checks (`->if()`). Sentry integrates via `Integration::handles($exceptions)` in bootstrap/app.php. Pennant provides class-based feature flags. Multi-stage Dockerfile keeps production images small (~150MB).

---

## Tier 1: Production Features (Monitoring & Security)

### What went wrong?
- Composer's `pre-package-uninstall` script from Laravel Boost caused installation issues - had to use `--no-scripts` flag
- Initial attempt to use `health: '/up'` alone wasn't enough - Spatie health checks provide more comprehensive monitoring beyond basic "up" status

### What went well?
- Sentry integration was straightforward - `Integration::handles($exceptions)` in bootstrap/app.php handles everything
- Spatie laravel-health provides conditional checks (production-only, database-specific) - `->if()` method is elegant
- Release-please automates changelog generation from conventional commits - no manual changelog maintenance
- Gitleaks with custom `.gitleaks.toml` reduces false positives from test files and example configs
- CodeQL adds SAST without any code changes - just a workflow file
- Dependency-review-action catches vulnerable packages before merge

### Why we chose this direction
- **Sentry over custom logging**: Sentry provides error grouping, release tracking, performance monitoring, and alerting out of the box. Building this would take months.
- **Spatie Health over custom endpoints**: Package provides 15+ built-in checks (database, cache, redis, horizon, queue, disk, etc.). Conditional execution handles environment differences.
- **Release-please over conventional-changelog**: Google's release-please creates PRs with changelogs, auto-bumps versions, and creates GitHub releases. Less maintenance than npm-based tools.
- **Gitleaks over trufflesecurity**: Gitleaks has cleaner false-positive management via `.gitleaks.toml`. Free for open source. Easy GitHub Action integration.
- **CodeQL for JavaScript only**: PHP CodeQL support is limited. JavaScript/TypeScript analysis catches XSS, prototype pollution, SQL injection in frontend code.
- **Health check routes separate from `/up`**: `/up` is for load balancer basic checks. `/health` provides detailed diagnostics. `/health/json` is for automated monitoring.
- **Scheduled security scans**: Weekly cron job catches vulnerabilities even when not actively developing.

### Code Pattern
```php
// Conditional health checks in AppServiceProvider
Health::checks([
    DatabaseCheck::new(),
    CacheCheck::new(),
    DebugModeCheck::new()->if(app()->isProduction()),
    HorizonCheck::new()->if(config('queue.default') === 'redis'),
]);

// Sentry integration in bootstrap/app.php
->withExceptions(function (Exceptions $exceptions): void {
    Integration::handles($exceptions);
});
```

---

## Tier 2: Scaling Features (Feature Flags & Load Testing)

### What went wrong?
- Laravel Pennant docs search returned no results from Boost - had to rely on package knowledge
- k6 load test scripts need a results directory created before run - added `mkdir -p` to workflow

### What went well?
- Pennant's class-based features are elegant - each feature is a self-contained file with clear documentation
- Conditional feature resolution (lottery, user attributes, config) covers all common rollout patterns
- Codecov.yml configuration is straightforward - ignores appropriate directories and sets reasonable thresholds
- k6 load testing provides three tiers: smoke (sanity), load (normal), stress (breaking point)
- Load test workflow supports manual dispatch with environment selection - safe for staging vs production

### Why we chose this direction
- **Pennant over custom flags**: Laravel Pennant provides database-backed feature storage, A/B testing support via Lottery, and clean class-based feature definitions. Rolling your own misses these patterns.
- **Class-based features over closures**: Each feature in `app/Features/` is documented, testable, and follows consistent patterns. Closures in `AppServiceProvider` become unwieldy at scale.
- **Codecov over Coveralls**: Codecov has better GitHub integration, PR comments with coverage diff, and flag support for separating unit/feature tests.
- **k6 over JMeter/Gatling**: k6 uses JavaScript (familiar to web devs), has better CI integration, and produces cleaner output. JMeter's XML config is harder to version control.
- **Three test levels**: Smoke (1 VU, 30s) catches obvious breaks. Load (10-50 VUs, 20min) simulates normal traffic. Stress (up to 300 VUs) finds breaking points.
- **Manual trigger for load tests**: Scheduled smoke tests are safe, but load/stress tests should be intentional. workflow_dispatch prevents accidental resource usage.

### Code Pattern
```php
// Feature flag with lottery rollout
final class AdvancedAnalytics
{
    public function resolve(User $user): bool
    {
        return Lottery::odds(1, 10)->choose(); // 10% rollout
    }
}

// Feature flag based on user state
final class TeamAnalytics
{
    public function resolve(User $user): bool
    {
        return $user->repositories()->count() >= 3;
    }
}

// Using feature flags in controllers
use Laravel\Pennant\Feature;

if (Feature::active(AdvancedAnalytics::class)) {
    // Show advanced analytics
}
```

```javascript
// k6 load test with thresholds
export const options = {
    stages: [
        { duration: '2m', target: 10 },  // Ramp up
        { duration: '5m', target: 50 },  // Peak load
        { duration: '2m', target: 0 },   // Ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<1000'], // 95% under 1s
        http_req_failed: ['rate<0.05'],    // <5% failures
    },
};
```

---

## Tier 3: Polish Features (Docker & API Docs)

### What went wrong?
- Dockerfile multi-stage build requires careful ordering - frontend build needs vendor directory for Wayfinder types
- Supervisord config needed to manage both nginx and php-fpm in single container (plus workers and scheduler)
- MySQL init.sql permissions needed explicit GRANT for testing database

### What went well?
- **Docker is optional** - app works with Herd, Valet, traditional PHP, or Docker. Multiple deployment paths documented.
- Multi-stage Docker build keeps final image small (~150MB) while having full build capabilities
- Docker Compose profiles allow optional worker/scheduler containers without cluttering default startup
- Scramble auto-generates OpenAPI docs from route definitions - zero manual spec writing
- Pest mutation testing is built into Pest 4 - no extra package needed, just `--mutate` flag
- Visual regression tests already existed in codebase - just needed CI workflow
- Created comprehensive `docs/DEPLOYMENT.md` covering Herd, Valet, Forge, Vapor, VPS, and Docker

### Why we chose this direction
- **Environment agnostic**: Application works on Laravel Herd, Valet, traditional servers, Docker, or serverless (Vapor). Docker is an option, not a requirement.
- **Multi-stage Dockerfile over single stage**: Separating build stages (base, composer, frontend, production) enables caching and reduces final image size. Production image doesn't include dev dependencies or build tools.
- **Supervisord over separate containers**: For simple deployments, single container with nginx + php-fpm + workers is easier to manage. For Kubernetes, you'd split these into separate containers.
- **Alpine over Ubuntu base**: Alpine images are ~5MB vs Ubuntu's ~70MB. PHP extensions install fine on Alpine with apk.
- **Scramble over L5-Swagger**: Scramble infers API structure from code - no annotations needed. L5-Swagger requires manual OpenAPI annotations which drift from actual implementation.
- **Mutation testing in CI for PRs only**: Full mutation testing is slow. Running on PRs to main catches issues before merge. Weekly scheduled runs provide comprehensive coverage.
- **Visual regression on path changes**: Only run visual tests when frontend/routes change. Avoids wasting CI resources on backend-only changes.

### Code Pattern
```dockerfile
# Multi-stage Dockerfile pattern
FROM php:8.4-fpm-alpine AS base
# Install extensions...

FROM base AS composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

FROM node:22-alpine AS frontend
COPY --from=composer /app/vendor ./vendor  # Need vendor for Wayfinder
RUN npm run build

FROM base AS production
COPY --from=composer /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
```

```yaml
# Docker Compose profiles for optional services
services:
  worker:
    profiles:
      - worker  # Only starts with: docker compose --profile worker up
```

### Docker Commands Reference
```bash
# Build and run
docker compose up -d

# With workers
docker compose --profile worker up -d

# View logs
docker compose logs -f app

# Shell access
docker compose exec app sh

# Production build
docker build -t gitpulse:latest --target production .
```

---

## Entry Template

```markdown
## [Feature Name]

### What went wrong?
- Issue description and root cause

### What went well?
- Success description and contributing factors

### Why we chose this direction
- Decision and reasoning
```

# GitPulse

Developer Productivity Analytics Platform - Transform Git commit activity into actionable productivity insights.

## About

GitPulse automatically documents developer work by listening to GitHub webhooks (commits, pushes, pull requests), generates comparative analytics, and produces intelligent weekly reports suitable for stakeholder communication.

**Tech Stack:** Laravel 12 + Vue.js 3.5 + Inertia.js 2.0 + Tailwind CSS 4 + SQLite/MySQL + Redis + Reverb

## Requirements

- PHP 8.4+
- Node.js 20+
- Composer 2.x
- SQLite (development) or MySQL 8.0+ (production)
- Redis (for queues and caching)

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/your-org/gitpulse.git
cd gitpulse
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure environment variables

Edit `.env` and configure:

```env
# Database (SQLite for development)
DB_CONNECTION=sqlite

# GitHub OAuth (required for authentication)
GITHUB_CLIENT_ID=your_client_id
GITHUB_CLIENT_SECRET=your_client_secret
GITHUB_REDIRECT_URI=http://localhost:8000/auth/github/callback

# GitHub Webhooks (for commit tracking)
GITHUB_WEBHOOK_SECRET=your_webhook_secret
```

### 5. Database setup

```bash
php artisan migrate
php artisan db:seed  # Optional: seed sample data
```

### 6. Build frontend assets

```bash
npm run build
```

## Development

### Start development servers

```bash
# Option 1: Using Composer script (recommended)
composer run dev

# Option 2: Manual start
php artisan serve &
npm run dev
```

The application will be available at `http://localhost:8000`

### Running tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Auth/RegistrationTest.php

# Run with filter
php artisan test --filter=registration

# Run architecture tests
php artisan test tests/Feature/ArchitectureTest.php
```

### Code quality

```bash
# Static analysis (PHPStan Level 8)
vendor/bin/phpstan analyze

# Code style (Laravel Pint)
vendor/bin/pint

# TypeScript type checking
npm run type-check

# ESLint
npm run lint

# All frontend checks
npm run lint:check && npm run type-check
```

### Useful commands

```bash
# Generate Wayfinder routes (TypeScript route types)
php artisan wayfinder:generate

# Clear all caches
php artisan optimize:clear

# Run queue worker
php artisan queue:work

# Run Horizon (queue dashboard)
php artisan horizon
```

## Project Structure

```
app/
├── Actions/          # CQRS command handlers (mutations)
├── Concerns/         # Reusable traits
├── Constants/        # PHP 8.1+ enums
├── Contracts/        # Interfaces (Action, Query)
├── DTOs/             # Data Transfer Objects
├── Exceptions/       # Custom exception handling
├── Http/
│   ├── Controllers/  # Thin controllers
│   ├── Middleware/   # Request middleware
│   └── Requests/     # Form request validation
├── Models/           # Eloquent models
├── Providers/        # Service providers
└── Queries/          # CQRS query handlers (reads)

resources/
├── css/              # Tailwind CSS
└── js/
    ├── components/   # Vue components
    ├── composables/  # Vue composables
    ├── layouts/      # Page layouts
    ├── lib/          # Utilities
    ├── pages/        # Inertia pages
    └── types/        # TypeScript types

tests/
├── Browser/          # Pest browser tests
├── Feature/          # Feature tests
└── Unit/             # Unit tests
```

## Architecture

### CQRS Pattern

- **Actions** (`app/Actions/`): Handle write operations, implement `App\Contracts\Action`
- **Queries** (`app/Queries/`): Handle read operations, implement `App\Contracts\Query`

### Key Conventions

- All PHP files use `declare(strict_types=1)`
- DTOs are `final readonly` classes
- Constants are backed PHP enums
- Controllers are thin - business logic in Actions/Queries
- Validation in Form Request classes

See `CLAUDE.md` for comprehensive development guidelines.

## Documentation

- [Product Requirements](docs/GitPulse_PRD_v2.4_FINAL.md)
- [Implementation Plan](docs/IMPLEMENTATION_PLAN.md)
- [Brand Guidelines](docs/BRAND_GUIDELINES.md)
- [Laravel Best Practices](docs/LARAVEL_BEST_PRACTICES.md)

## Contributing

1. Create a feature branch from `main`
2. Write tests for new functionality
3. Ensure all quality checks pass:
   ```bash
   vendor/bin/phpstan analyze
   vendor/bin/pint
   npm run lint
   npm run type-check
   php artisan test
   ```
4. Submit a pull request

## License

Proprietary - All rights reserved.

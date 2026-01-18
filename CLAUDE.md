<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.17
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Package Selection Hierarchy (Critical)
Before implementing any significant feature, follow this strict package selection hierarchy to keep the project simple, maintainable, and leverage battle-tested solutions:

1. **Laravel MCP Tools First**: Use the Laravel Boost MCP tools (`search-docs`, `tinker`, `database-query`, etc.) to research and debug before writing code.

2. **Laravel First-Party Packages**: Check if Laravel offers a first-party solution:
   - Authentication: Fortify, Sanctum, Passport, Socialite
   - Queues/Jobs: Horizon
   - Real-time: Reverb, Echo
   - Search: Scout
   - Payments: Cashier
   - Feature Flags: Pennant
   - Storage: Filesystem with S3/local drivers
   - Mail: Built-in Mail with various drivers
   - Notifications: Built-in notification system
   - PDF: Use `barryvdh/laravel-dompdf` (Laravel-endorsed)

3. **Spatie Packages**: If Laravel doesn't have a first-party solution, check Spatie packages:
   - Permissions: `spatie/laravel-permission`
   - Media: `spatie/laravel-medialibrary`
   - Activity Log: `spatie/laravel-activitylog`
   - Settings: `spatie/laravel-settings`
   - Data Transfer: `spatie/laravel-data`
   - Query Builder: `spatie/laravel-query-builder`
   - Webhooks: `spatie/laravel-webhook-client`, `spatie/laravel-webhook-server`
   - Backup: `spatie/laravel-backup`
   - Health: `spatie/laravel-health`
   - Tags: `spatie/laravel-tags`
   - Slugs: `spatie/laravel-sluggable`
   - Translatable: `spatie/laravel-translatable`
   - Visit the Spatie package list: https://spatie.be/open-source/packages

4. **Custom Implementation**: Only implement custom solutions when:
   - No suitable package exists
   - The package would be overkill for a simple requirement
   - The user explicitly requests a custom implementation

**Always use `search-docs` to check Laravel ecosystem documentation before implementing features.**

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

## Lessons Learned
Before making architectural decisions, check `docs/lessons/LESSONS.md` for past decisions and their reasoning. This document captures:
- What went wrong in previous sprints (mistakes to avoid)
- What went well (patterns to follow)
- Why we chose specific directions (reasoning behind decisions)

**When to check lessons:**
- Before choosing between implementation approaches
- Before creating migrations (e.g., column types)
- Before adding new packages or patterns
- When facing a decision that feels like it could go multiple ways

**Update lessons after completing work** to capture new learnings for future reference.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

## Inertia

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (`vite.config.js`).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use the `search-docs` tool for accurate guidance on all things Inertia.

<code-snippet name="Inertia Render Example" lang="php">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>

=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 and v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Deferred props.
- Infinite scrolling using merging props and `WhenVisible`.
- Lazy loading data on scroll.
- Polling.
- Prefetching.

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing/animated skeleton.

### Inertia Form General Guidance
- Build forms using the `useForm` helper. Use the code examples and the `search-docs` tool with a query of `useForm helper` for guidance.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Database Compatibility (SQLite & MySQL)
This application uses SQLite for development/testing and MySQL for production. All database code must work on both:

- **Never use `enum()` column type in migrations** - Use `string()` with a sensible length instead:
  - Status fields: `string('status', 20)` (e.g., 'pending', 'active', 'completed')
  - Short codes: `string('code', 10)`
  - Identifiers: `string('type', 50)`
  - General text that fits a category: `string('category', 100)`

- **Use the `DatabaseCompatible` trait** for queries requiring database-specific functions:
  - Date extraction: `yearFromDate()`, `monthFromDate()`, `dayFromDate()`
  - Date formatting: `dateFormat()`
  - Current timestamp: `currentTimestamp()`, `currentDate()`
  - String operations: `concat()`, `groupConcat()`, `coalesce()`
  - Date math: `dateDiffDays()`, `dateAddDays()`

- **Avoid raw SQL with database-specific functions** like `YEAR()`, `MONTH()`, `NOW()`, `DATE_FORMAT()`, `GROUP_CONCAT()` - these differ between MySQL and SQLite.

- **Use Laravel's query builder** which handles most cross-database compatibility automatically.

<code-snippet name="Using DatabaseCompatible Trait" lang="php">
use App\Concerns\DatabaseCompatible;

class MyQuery implements Query
{
    use DatabaseCompatible;

    public function get(): Collection
    {
        return Model::query()
            ->selectRaw($this->yearFromDate('created_at') . ' as year')
            ->groupByRaw($this->yearFromDate('created_at'))
            ->get();
    }
}
</code-snippet>

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version-specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== wayfinder/core rules ===

## Laravel Wayfinder

Wayfinder generates TypeScript functions and types for Laravel controllers and routes which you can import into your client-side code. It provides type safety and automatic synchronization between backend routes and frontend code.

### Development Guidelines
- Always use the `search-docs` tool to check Wayfinder correct usage before implementing any features.
- Always prefer named imports for tree-shaking (e.g., `import { show } from '@/actions/...'`).
- Avoid default controller imports (prevents tree-shaking).
- Run `php artisan wayfinder:generate` after route changes if Vite plugin isn't installed.

### Feature Overview
- Form Support: Use `.form()` with `--with-form` flag for HTML form attributes — `<form {...store.form()}>` → `action="/posts" method="post"`.
- HTTP Methods: Call `.get()`, `.post()`, `.patch()`, `.put()`, `.delete()` for specific methods — `show.head(1)` → `{ url: "/posts/1", method: "head" }`.
- Invokable Controllers: Import and invoke directly as functions. For example, `import StorePost from '@/actions/.../StorePostController'; StorePost()`.
- Named Routes: Import from `@/routes/` for non-controller routes. For example, `import { show } from '@/routes/post'; show(1)` for route name `post.show`.
- Parameter Binding: Detects route keys (e.g., `{post:slug}`) and accepts matching object properties — `show("my-post")` or `show({ slug: "my-post" })`.
- Query Merging: Use `mergeQuery` to merge with `window.location.search`, set values to `null` to remove — `show(1, { mergeQuery: { page: 2, sort: null } })`.
- Query Parameters: Pass `{ query: {...} }` in options to append params — `show(1, { query: { page: 1 } })` → `"/posts/1?page=1"`.
- Route Objects: Functions return `{ url, method }` shaped objects — `show(1)` → `{ url: "/posts/1", method: "get" }`.
- URL Extraction: Use `.url()` to get URL string — `show.url(1)` → `"/posts/1"`.

### Example Usage

<code-snippet name="Wayfinder Basic Usage" lang="typescript">
    // Import controller methods (tree-shakable)...
    import { show, store, update } from '@/actions/App/Http/Controllers/PostController'

    // Get route object with URL and method...
    show(1) // { url: "/posts/1", method: "get" }

    // Get just the URL...
    show.url(1) // "/posts/1"

    // Use specific HTTP methods...
    show.get(1) // { url: "/posts/1", method: "get" }
    show.head(1) // { url: "/posts/1", method: "head" }

    // Import named routes...
    import { show as postShow } from '@/routes/post' // For route name 'post.show'
    postShow(1) // { url: "/posts/1", method: "get" }
</code-snippet>

### Wayfinder + Inertia
If your application uses the `useForm` component from Inertia, you can directly submit to the Wayfinder generated functions.

<code-snippet name="Wayfinder useForm Example" lang="typescript">
    import { store } from "@/actions/App/Http/Controllers/ExampleController";

    const form = useForm({
        name: "My Big Post",
    });

    form.submit(store());
</code-snippet>

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest
### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest {name}`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests that have a lot of duplicated data. This is often the case when testing validation rules, so consider this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>

=== pest/v4 rules ===

## Pest 4

- Pest 4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest 4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>

=== webhooks rules ===

## Webhooks (Spatie Packages Required)

**Always use Spatie webhook packages for webhook implementations:**

- **Receiving webhooks**: Use `spatie/laravel-webhook-client`
- **Sending webhooks**: Use `spatie/laravel-webhook-server`

### Receiving Webhooks (webhook-client)

When implementing webhook receivers:

1. Create a custom `SignatureValidator` for the provider's signature format
2. Create a `WebhookProfile` to filter which events to process
3. Create a job extending `ProcessWebhookJob` to handle the events
4. Configure in `config/webhook-client.php`
5. Exclude webhook routes from CSRF protection in `bootstrap/app.php`

<code-snippet name="Webhook Client Structure" lang="php">
// app/Webhooks/GitHubSignatureValidator.php
final class GitHubSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = $request->header($config->signatureHeaderName);
        $computed = 'sha256=' . hash_hmac('sha256', $request->getContent(), $config->signingSecret);
        return hash_equals($computed, $signature);
    }
}

// app/Jobs/ProcessGitHubWebhookJob.php
class ProcessGitHubWebhookJob extends ProcessWebhookJob
{
    public function handle(): void
    {
        $payload = $this->webhookCall->payload;
        $event = $this->webhookCall->headers['x-github-event'][0] ?? null;
        // Process event...
    }
}
</code-snippet>

### Sending Webhooks (webhook-server)

When implementing webhook senders:

1. Use `WebhookCall::create()` to dispatch webhooks
2. Configure signature algorithm (typically HMAC SHA-256)
3. Implement retry logic for failed deliveries

=== laravel/socialite rules ===

## Laravel Socialite

Socialite provides OAuth authentication with GitHub (and other providers). This application uses Socialite for GitHub OAuth login and API access.

### Configuration
- Credentials are stored in `config/services.php` under `github`
- Scopes are configured dynamically using `OAuthProvider` enum
- Routes: `/auth/github` (redirect) and `/auth/github/callback` (callback)

### Usage Pattern
- Use `Socialite::driver('github')->scopes([...])->redirect()` to initiate OAuth
- Use `Socialite::driver('github')->user()` to get the authenticated user
- Cast the user to `Laravel\Socialite\Two\User` for type safety and access to `->token`

<code-snippet name="Socialite Usage" lang="php">
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

// Redirect to GitHub
public function redirect(): RedirectResponse
{
    return Socialite::driver('github')
        ->scopes(OAuthProvider::GitHub->scopes())
        ->redirect();
}

// Handle callback
public function callback(): RedirectResponse
{
    /** @var SocialiteUser $githubUser */
    $githubUser = Socialite::driver('github')->user();

    // Access token for API calls
    $token = $githubUser->token;
}
</code-snippet>

### Important Notes
- Always wrap `Socialite::driver()->user()` in try/catch for `InvalidStateException`
- Store the OAuth token securely for making GitHub API calls later
- OAuth redirects require regular anchors, not Inertia Link (see Frontend Guidelines)

=== laravel/fortify rules ===

## Laravel Fortify

Fortify is a headless authentication backend that provides authentication routes and controllers for Laravel applications.

**Before implementing any authentication features, use the `search-docs` tool to get the latest docs for that specific feature.**

### Configuration & Setup
- Check `config/fortify.php` to see what's enabled. Use `search-docs` for detailed information on specific features.
- Enable features by adding them to the `'features' => []` array: `Features::registration()`, `Features::resetPasswords()`, etc.
- To see the all Fortify registered routes, use the `list-routes` tool with the `only_vendor: true` and `action: "Fortify"` parameters.
- Fortify includes view routes by default (login, register). Set `'views' => false` in the configuration file to disable them if you're handling views yourself.

### Customization
- Views can be customized in `FortifyServiceProvider`'s `boot()` method using `Fortify::loginView()`, `Fortify::registerView()`, etc.
- Customize authentication logic with `Fortify::authenticateUsing()` for custom user retrieval / validation.
- Actions in `app/Actions/Fortify/` handle business logic (user creation, password reset, etc.). They're fully customizable, so you can modify them to change feature behavior.

## Available Features
- `Features::registration()` for user registration.
- `Features::emailVerification()` to verify new user emails.
- `Features::twoFactorAuthentication()` for 2FA with QR codes and recovery codes.
  - Add options: `['confirmPassword' => true, 'confirm' => true]` to require password confirmation and OTP confirmation before enabling 2FA.
- `Features::updateProfileInformation()` to let users update their profile.
- `Features::updatePasswords()` to let users change their passwords.
- `Features::resetPasswords()` for password reset via email.

=== gitpulse architecture ===

## CQRS Architecture (Actions & Queries)

This application uses a simplified CQRS pattern to keep controllers thin and business logic organized:

- **Actions** (`app/Actions`): Handle write operations (create, update, delete)
- **Queries** (`app/Queries`): Handle read operations (never modify state)
- **Contracts** (`app/Contracts`): Define interfaces for Actions and Queries

### Rules for Actions
- Must implement `App\Contracts\Action`
- Must be `final` classes
- Must have `Action` suffix (e.g., `ConnectGitHubAction`)
- Execute via `->execute()` method
- Handle one specific mutation per class
- Fortify actions are exempt (they follow Laravel's convention)

### Rules for Queries
- Must implement `App\Contracts\Query`
- Must be `final` classes
- Must have `Query` suffix (e.g., `FindUserByGitHubIdQuery`)
- Execute via `->get()` method
- Never modify database state
- Return data for Inertia::render()

<code-snippet name="CQRS Action Example" lang="php">
final class ConnectGitHubAction implements Action
{
    public function __construct(
        private readonly User $user,
        private readonly SocialiteUser $githubUser,
    ) {}

    public function execute(): bool
    {
        $this->user->update([...]);
        return true;
    }
}

// Usage in controller:
(new ConnectGitHubAction($user, $githubUser))->execute();
</code-snippet>

<code-snippet name="CQRS Query Example" lang="php">
final class FindUserByGitHubIdQuery implements Query
{
    public function __construct(
        private readonly string $githubId,
    ) {}

    public function get(): ?User
    {
        return User::where('github_id', $this->githubId)->first();
    }
}

// Usage in controller:
$user = (new FindUserByGitHubIdQuery($githubId))->get();
</code-snippet>

## DTOs (Data Transfer Objects)

DTOs live in `app/DTOs` and are used for type-safe data passing between layers.

### Rules for DTOs
- Must be `final readonly` classes
- Use constructor property promotion
- Include factory methods like `fromSocialite()`, `fromRequest()`, `fromArray()`
- Include `toArray()` for database storage when needed
- No business logic - only data transformation

<code-snippet name="DTO Example" lang="php">
final readonly class GitHubUserData
{
    public function __construct(
        public string $id,
        public string $username,
        public ?string $name,
        public ?string $email,
        public ?string $avatar,
        public ?string $token,
    ) {}

    public static function fromSocialite(SocialiteUser $user): self
    {
        return new self(
            id: $user->getId(),
            username: $user->getNickname() ?? '',
            // ...
        );
    }

    public function toArray(): array
    {
        return ['github_id' => $this->id, ...];
    }
}
</code-snippet>

## Constants (Enums)

PHP 8.1+ enums live in `app/Constants` (not `app/Enums`) for type-safe constants.

### Rules for Constants
- Must be backed enums (string or int)
- Enum case names must be TitleCase (e.g., `GitHub`, `Pending`, `Active`)
- Include helper methods like `displayName()`, `iconName()`, `scopes()`
- Use for status fields, types, providers, and other fixed values

<code-snippet name="Enum/Constant Example" lang="php">
enum OAuthProvider: string
{
    case GitHub = 'github';

    public function displayName(): string
    {
        return match ($this) {
            self::GitHub => 'GitHub',
        };
    }

    public function scopes(): array
    {
        return match ($this) {
            self::GitHub => ['read:user', 'user:email', 'repo'],
        };
    }
}
</code-snippet>

## Architecture Tests

Architecture tests in `tests/Feature/ArchitectureTest.php` enforce coding standards automatically using Pest's `arch()` function.

### What Architecture Tests Enforce
- Strict types in all PHP files
- Controllers have `Controller` suffix
- Models extend Eloquent Model
- Requests extend FormRequest
- Jobs implement ShouldQueue
- Services have `Service` suffix
- Middleware have `Middleware` suffix
- Actions implement `Action` contract and are `final`
- Queries implement `Query` contract and are `final`
- DTOs are `final readonly`
- Constants are enums
- No debugging statements (dd, dump, ray)
- No deprecated PHP functions
- Controllers don't use `DB::` facade

**Always run architecture tests after structural changes**: `php artisan test tests/Feature/ArchitectureTest.php`

=== development lifecycle ===

## Git Hooks (Husky)

This project uses Husky for Git hooks. All hooks are in `.husky/` directory.

### Hook Summary

| Hook | Purpose | Blocking |
|------|---------|----------|
| `pre-commit` | Runs lint-staged (Pint, ESLint, Prettier) | Yes |
| `commit-msg` | Enforces Conventional Commits format | Yes |
| `post-commit` | Reminds to document lessons learned | No |
| `post-merge` | Reminds to run dependency updates | No |
| `post-checkout` | Detects dependency/migration changes between branches | No |
| `pre-push` | Runs all quality gates before push | Yes |

### Pre-Push Quality Gates

The pre-push hook runs 5 checks in order:

1. **Branch Protection** - Blocks direct pushes to main/master/production
2. **Lessons Learned** - Blocks if `feat|fix|refactor|perf` commits lack LESSONS.md updates
3. **Static Analysis** - PHPStan level 8 must pass
4. **Test Suite** - All tests must pass
5. **Security Audit** - Runs `composer audit` + `npm audit` (warning only)

### Conventional Commits

All commits must follow the format: `type(scope): description`

Valid types: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`, `revert`

### Lessons Learned Requirement

For significant commits (`feat`, `fix`, `refactor`, `perf`), you must update `docs/lessons/LESSONS.md` before pushing. Use the `/lessons` skill or manually document:
- What went wrong?
- What went well?
- Why you chose this direction

## CI/CD Pipeline

GitHub Actions runs on push/PR to main/develop:

| Job | Purpose | Blocking |
|-----|---------|----------|
| `security` | `composer audit` + `npm audit` | Yes |
| `static-analysis` | PHPStan level 8 | Yes |
| `code-style` | Pint test mode | Yes |
| `frontend` | TypeScript, ESLint, Vite build | Yes |
| `tests` | Parallel tests with 70% coverage minimum | Yes |

All jobs must pass before merge.

## Dependency Management

### Dependabot

Dependabot is configured for weekly updates (Mondays 09:00 UTC):
- **Composer**: Groups Laravel packages, dev dependencies separately
- **NPM**: Groups Vue ecosystem, dev tooling separately
- **GitHub Actions**: Updates action versions

### Security Audits

Run locally before committing:
```bash
composer audit          # PHP dependencies
npm audit               # JavaScript dependencies
```

## Code Review

### CODEOWNERS

`.github/CODEOWNERS` assigns automatic reviewers by file path. Critical files (config, migrations, hooks) require explicit review.

### PR Template

All PRs use `.github/PULL_REQUEST_TEMPLATE.md` which includes:
- Change type classification
- Checklist for code quality, testing, security
- Related issues linking

=== frontend guidelines ===

## UI/UX Design Principles

- Keep UIs clean, simple, and professional
- Avoid visual clutter - use whitespace effectively
- Follow existing brand guidelines and color scheme
- Check existing components before creating new ones

## Inertia Link vs Regular Anchors

**Use Inertia `<Link>` for:**
- Internal SPA navigation within the application
- Any route that returns an Inertia response
- Dashboard, settings, profile pages

**Use regular `<a>` anchors for:**
- OAuth flows (GitHub, Google, etc.) - require full page redirects to external providers
- External URLs
- File downloads
- Any route that redirects to an external domain

<code-snippet name="Correct Link Usage" lang="vue">
<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <!-- Internal navigation - use Inertia Link -->
    <Link href="/dashboard">Dashboard</Link>

    <!-- OAuth - use regular anchor (redirects to external provider) -->
    <a href="/auth/github">Sign in with GitHub</a>

    <!-- External link - use regular anchor -->
    <a href="https://github.com" target="_blank">GitHub</a>
</template>
</code-snippet>

## Component Reusability

Before creating a new component:
1. Check `resources/js/components/` for existing components
2. Check `resources/js/components/ui/` for shadcn/ui components
3. If a similar component exists, extend or modify it
4. Only create new components when truly necessary

### Composable Components
For complex UI patterns like tabs, abstract them into composable pieces:
- Container component (e.g., `Tabs`)
- Trigger component (e.g., `TabsTrigger`)
- Content component (e.g., `TabsContent`)

## Custom Scrollbars

The application uses custom scrollbar styles defined in `resources/css/app.css`:
- `.scrollbar-hide` - hides scrollbar completely
- `.scrollbar-thin` - thin 4px scrollbar
- Default scrollbars are styled to match the theme

## Deferred Props & Loading States

When using Inertia v2 deferred props, always show loading states:
- Use pulsing/animated skeletons
- Match skeleton shape to expected content
- Provide meaningful empty states
</laravel-boost-guidelines>

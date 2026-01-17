<?php

declare(strict_types=1);

/**
 * Architecture Tests
 *
 * These tests enforce coding standards and architectural constraints
 * across the GitPulse codebase.
 */

// Strict types should be declared in all PHP files
arch('strict types are used in all files')
    ->expect('App')
    ->toUseStrictTypes();

// Controllers should have the correct suffix and extend Controller
arch('controllers have controller suffix')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

// Models should extend Eloquent Model
arch('models extend eloquent model')
    ->expect('App\Models')
    ->toExtend(\Illuminate\Database\Eloquent\Model::class);

// Requests should extend FormRequest
arch('requests extend form request')
    ->expect('App\Http\Requests')
    ->toExtend(\Illuminate\Foundation\Http\FormRequest::class);

// Jobs should implement ShouldQueue
arch('jobs implement should queue')
    ->expect('App\Jobs')
    ->toImplement(\Illuminate\Contracts\Queue\ShouldQueue::class);

// Actions in Fortify follow Laravel's naming convention (not our custom Action suffix)
arch('fortify actions follow laravel convention')
    ->expect('App\Actions\Fortify')
    ->toImplement(\Laravel\Fortify\Contracts\CreatesNewUsers::class)
    ->ignoring([
        \App\Actions\Fortify\ResetUserPassword::class,
        'App\Actions\Fortify\UpdateUserPassword',
        'App\Actions\Fortify\UpdateUserProfileInformation',
        'App\Actions\Fortify\PasswordValidationRules',
    ]);

// Ensure no debugging statements are left in the codebase
arch('no debugging statements')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

// Controllers should not use Eloquent directly (prefer repositories/services)
arch('controllers are thin')
    ->expect('App\Http\Controllers')
    ->not->toUse([\Illuminate\Support\Facades\DB::class]);

// Ensure env() is only used in config files
arch('env is only used in config')
    ->expect('env')
    ->toOnlyBeUsedIn('config');

// Middleware should have correct suffix (except common Laravel naming conventions)
arch('middleware have middleware suffix')
    ->expect('App\Http\Middleware')
    ->toHaveSuffix('Middleware')
    ->ignoring([
        \App\Http\Middleware\HandleInertiaRequests::class,
        \App\Http\Middleware\HandleAppearance::class,
    ]);

// Exceptions should extend Exception
arch('exceptions extend exception')
    ->expect('App\Exceptions')
    ->toExtend('Exception')
    ->ignoring(\App\Exceptions\Handler::class);

// Services should have Service suffix
arch('services have service suffix')
    ->expect('App\Services')
    ->toHaveSuffix('Service');

// Ensure models are not accessed from views/frontend-related code
arch('models are not exposed directly')
    ->expect('App\Models')
    ->not->toBeUsedIn([
        'App\Http\Middleware',
    ]);

// Ensure final classes where appropriate
arch('value objects should be final')
    ->expect('App\ValueObjects')
    ->toBeFinal();

// Ensure interfaces follow naming conventions
arch('interfaces have interface suffix')
    ->expect('App\Contracts')
    ->toBeInterfaces();

// Ensure traits follow naming conventions
arch('traits follow naming convention')
    ->expect('App\Concerns')
    ->toBeTraits();

// Constants (enums) are properly defined
arch('constants are enums')
    ->expect('App\Constants')
    ->toBeEnums();

// DTOs should be readonly final classes
arch('dtos are readonly final')
    ->expect('App\DTOs')
    ->toBeFinal()
    ->toBeReadonly();

// Prevent God classes - classes shouldn't have too many dependencies
arch('controllers have limited dependencies')
    ->expect('App\Http\Controllers')
    ->classes()
    ->not->toHaveMethod('__construct', fn ($method) => count($method->getParameters()) > 5);

// Ensure no deprecated functions are used
arch('no deprecated php functions')
    ->expect(['create_function', 'each', 'split', 'ereg', 'mysql_query'])
    ->not->toBeUsed();

// Feature tests should use strict types
arch('feature tests use strict types')
    ->expect('Tests\Feature')
    ->toUseStrictTypes();

// CQRS: Actions should implement Action contract
arch('actions implement action contract')
    ->expect('App\Actions')
    ->toImplement(\App\Contracts\Action::class)
    ->ignoring([
        'App\Actions\Fortify',
    ]);

// CQRS: Queries should implement Query contract
arch('queries implement query contract')
    ->expect('App\Queries')
    ->toImplement(\App\Contracts\Query::class);

// CQRS: Actions should be final classes
arch('actions should be final')
    ->expect('App\Actions')
    ->toBeFinal()
    ->ignoring([
        'App\Actions\Fortify',
    ]);

// CQRS: Queries should be final classes
arch('queries should be final')
    ->expect('App\Queries')
    ->toBeFinal();

// CQRS: Actions should have Action suffix
arch('actions have action suffix')
    ->expect('App\Actions')
    ->toHaveSuffix('Action')
    ->ignoring([
        'App\Actions\Fortify',
    ]);

// CQRS: Queries should have Query suffix
arch('queries have query suffix')
    ->expect('App\Queries')
    ->toHaveSuffix('Query');

// Integrations: Connectors should extend Saloon Connector
arch('connectors extend saloon connector')
    ->expect('App\Integrations\GitHub\GitHubConnector')
    ->toExtend(\Saloon\Http\Connector::class);

// Integrations: Connectors should be final
arch('connectors are final')
    ->expect('App\Integrations\GitHub\GitHubConnector')
    ->toBeFinal();

// Integrations: Connectors should have Connector suffix
arch('connectors have connector suffix')
    ->expect('App\Integrations\GitHub\GitHubConnector')
    ->toHaveSuffix('Connector');

// Integrations: Requests should extend Saloon Request
arch('integration requests extend saloon request')
    ->expect('App\Integrations\GitHub\Requests')
    ->toExtend(\Saloon\Http\Request::class);

// Integrations: Requests should be final
arch('integration requests are final')
    ->expect('App\Integrations\GitHub\Requests')
    ->toBeFinal();

// Integrations: Requests should have Request suffix
arch('integration requests have request suffix')
    ->expect('App\Integrations\GitHub\Requests')
    ->toHaveSuffix('Request');

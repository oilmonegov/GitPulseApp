<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Error Messages
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for various error messages that
    | we need to display to the user. Feel free to modify these according
    | to your application's requirements.
    |
    */

    // HTTP Status Errors
    'http' => [
        '400' => 'The request could not be processed. Please check your input and try again.',
        '401' => 'You need to sign in to access this resource.',
        '403' => 'You do not have permission to access this resource.',
        '404' => 'The requested resource could not be found.',
        '405' => 'This action is not allowed.',
        '408' => 'The request timed out. Please try again.',
        '419' => 'Your session has expired. Please refresh the page and try again.',
        '422' => 'The provided data was invalid. Please review and correct the errors.',
        '429' => 'Too many requests. Please wait a moment and try again.',
        '500' => 'An unexpected error occurred. Our team has been notified.',
        '502' => 'The service is temporarily unavailable. Please try again later.',
        '503' => 'The service is currently under maintenance. Please try again later.',
        '504' => 'The request timed out. Please try again.',
    ],

    // Authentication Errors
    'auth' => [
        'unauthenticated' => 'Please sign in to continue.',
        'unauthorized' => 'You do not have permission to perform this action.',
        'github_failed' => 'GitHub authentication failed. Please try again.',
        'github_already_linked' => 'This GitHub account is already linked to another user.',
        'github_not_connected' => 'Your GitHub account is not connected. Please connect it first.',
        'password_required' => 'Please set a password before disconnecting GitHub.',
        'session_expired' => 'Your session has expired. Please sign in again.',
        'invalid_credentials' => 'The provided credentials are incorrect.',
    ],

    // Validation Errors
    'validation' => [
        'failed' => 'Validation failed. Please check your input.',
        'invalid_data' => 'The provided data is invalid.',
    ],

    // GitHub API Errors
    'github' => [
        'auth_failed' => 'GitHub authentication failed. Please try again.',
        'already_linked' => 'This GitHub account is already linked to another user.',
        'connected' => 'GitHub account connected successfully.',
        'disconnected' => 'GitHub account disconnected successfully.',
        'rate_limited' => 'GitHub API rate limit exceeded. Please try again later.',
        'api_error' => 'An error occurred while communicating with GitHub.',
        'webhook_failed' => 'Failed to set up webhook. Please try again.',
        'repository_not_found' => 'The requested repository could not be found.',
        'permission_denied' => 'You do not have access to this repository.',
    ],

    // Database Errors
    'database' => [
        'connection_failed' => 'Unable to connect to the database. Please try again later.',
        'query_failed' => 'A database error occurred. Please try again.',
    ],

    // Generic Errors
    'generic' => [
        'unexpected' => 'An unexpected error occurred. Please try again.',
        'server_error' => 'A server error occurred. Our team has been notified.',
        'not_found' => 'The requested resource could not be found.',
        'maintenance' => 'The application is currently under maintenance.',
    ],
];

<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notifications' => ['sometimes', 'array'],
            'notifications.weekly_digest' => ['sometimes', 'boolean'],
            'notifications.commit_summary' => ['sometimes', 'boolean'],
            'notifications.repository_alerts' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notifications.array' => 'Notification preferences must be an array.',
            'notifications.weekly_digest.boolean' => 'Weekly digest preference must be true or false.',
            'notifications.commit_summary.boolean' => 'Commit summary preference must be true or false.',
            'notifications.repository_alerts.boolean' => 'Repository alerts preference must be true or false.',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\ExportUserDataAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DataManagementController extends Controller
{
    /**
     * Display the data management page.
     */
    public function index(Request $request): InertiaResponse
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('settings/Data', [
            'stats' => [
                'total_commits' => $user->commits()->count(),
                'total_repositories' => $user->repositories()->count(),
            ],
        ]);
    }

    /**
     * Export the user's data.
     */
    public function export(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $format = $request->input('format', 'json');

        if (! in_array($format, ['json', 'csv'])) {
            $format = 'json';
        }

        /** @var 'json'|'csv' $format */
        $export = (new ExportUserDataAction($user, $format))->execute();

        return response($export['content'], 200, [
            'Content-Type' => $export['mime_type'],
            'Content-Disposition' => 'attachment; filename="' . $export['filename'] . '"',
        ]);
    }
}

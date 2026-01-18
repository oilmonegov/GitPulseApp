<x-mail::message>
# Your Weekly GitPulse Digest

Hi {{ $userName }},

Here's a summary of your coding activity this week.

## This Week's Highlights

<x-mail::panel>
**{{ number_format($totalCommits) }}** commits pushed<br>
**{{ number_format($totalAdditions) }}** lines added<br>
**{{ number_format($totalDeletions) }}** lines removed<br>
**{{ $averageImpact }}** average impact score
</x-mail::panel>

@if(count($topRepositories) > 0)
## Most Active Repositories

<x-mail::table>
| Repository | Commits |
|:-----------|--------:|
@foreach($topRepositories as $repo)
| {{ $repo['name'] }} | {{ $repo['commits'] }} |
@endforeach
</x-mail::table>
@endif

<x-mail::button :url="config('app.url') . '/dashboard'">
View Dashboard
</x-mail::button>

Keep up the great work!

Thanks,<br>
{{ config('app.name') }}

<x-mail::subcopy>
You're receiving this email because you have weekly digest notifications enabled.
You can update your preferences in your [notification settings]({{ config('app.url') }}/settings/notifications).
</x-mail::subcopy>
</x-mail::message>

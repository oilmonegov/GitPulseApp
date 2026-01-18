<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  array{total_commits: int, total_additions: int, total_deletions: int, average_impact: float, top_repositories: array<int, array{name: string, commits: int}>}  $stats
     */
    public function __construct(
        public readonly User $user,
        public readonly array $stats,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Weekly GitPulse Digest',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.weekly-digest',
            with: [
                'userName' => $this->user->name,
                'totalCommits' => $this->stats['total_commits'],
                'totalAdditions' => $this->stats['total_additions'],
                'totalDeletions' => $this->stats['total_deletions'],
                'averageImpact' => $this->stats['average_impact'],
                'topRepositories' => $this->stats['top_repositories'],
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

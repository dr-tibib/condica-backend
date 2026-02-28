<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManagerDailyBriefingMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{
     *     manager: \App\Models\Employee,
     *     date: string,
     *     present: array<int, string>,
     *     absent: array<int, string>,
     *     on_leave: array<int, string>,
     *     on_delegation: array<int, string>,
     *     unclosed_yesterday: array<int, string>,
     * }  $teamData
     */
    public function __construct(
        public readonly array $teamData,
        public readonly string $briefingText
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Raport zilnic echipă - '.$this->teamData['date'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.manager-briefing',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

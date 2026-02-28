<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Mail\ManagerDailyBriefingMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ManagerDailyBriefing extends Notification implements ShouldQueue
{
    use Queueable;

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

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        $channels = ['mail'];

        if (! empty($notifiable->whatsapp_number)) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    public function toMail(mixed $notifiable): ManagerDailyBriefingMail
    {
        return new ManagerDailyBriefingMail($this->teamData, $this->briefingText);
    }

    public function toWhatsApp(mixed $notifiable): string
    {
        $date = $this->teamData['date'];
        $managerName = $this->teamData['manager']->name;
        $absentCount = count($this->teamData['absent']);
        $unclosedCount = count($this->teamData['unclosed_yesterday']);

        $text = "📋 *Raport zilnic echipă - {$date}*\n\n";
        $text .= $this->briefingText;

        if ($absentCount > 0 || $unclosedCount > 0) {
            $text .= "\n\n⚠️ Atenție: {$absentCount} absenți";
            if ($unclosedCount > 0) {
                $text .= ", {$unclosedCount} pontaje neînchise.";
            } else {
                $text .= '.';
            }
        }

        return $text;
    }
}

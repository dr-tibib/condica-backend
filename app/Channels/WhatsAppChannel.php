<?php

declare(strict_types=1);

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Twilio\Rest\Client as TwilioClient;

class WhatsAppChannel
{
    public function __construct(private readonly TwilioClient $twilio) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $to = $notifiable->routeNotificationFor('whatsApp', $notification);

        if (empty($to)) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);

        $this->twilio->messages->create(
            'whatsapp:'.$to,
            [
                'from' => config('services.twilio.whatsapp_from'),
                'body' => $message,
            ]
        );
    }
}

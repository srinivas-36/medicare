<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClinicNotification extends Notification
{
    use Queueable;

    /**
     * @param  array{title: string, body: string, url?: string|null}  $payload
     */
    public function __construct(public array $payload) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, body: string, url: string|null}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->payload['title'],
            'body' => $this->payload['body'],
            'url' => $this->payload['url'] ?? null,
        ];
    }
}

<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

abstract class RealtimeDatabaseNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return array_values(array_unique([
            'database',
            'broadcast',
            ...$this->additionalChannels($notifiable),
        ]));
    }

    public function toArray(object $notifiable): array
    {
        return $this->notificationData($notifiable);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->notificationData($notifiable));
    }

    protected function additionalChannels(object $notifiable): array
    {
        return [];
    }

    abstract protected function notificationData(object $notifiable): array;
}

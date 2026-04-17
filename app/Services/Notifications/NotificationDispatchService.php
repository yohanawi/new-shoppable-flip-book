<?php

namespace App\Services\Notifications;

use App\Models\NotificationDelivery;
use Illuminate\Database\QueryException;
use Illuminate\Notifications\Notification;

class NotificationDispatchService
{
    public function sendOnce(object $notifiable, Notification $notification, string $uniqueKey, array $context = []): bool
    {
        $delivery = [
            'notifiable_type' => $notifiable::class,
            'notifiable_id' => $notifiable->getKey(),
            'notification_type' => $notification::class,
            'unique_key' => $uniqueKey,
        ];

        if (NotificationDelivery::query()->where($delivery)->exists()) {
            return false;
        }

        $notifiable->notify($notification);

        try {
            NotificationDelivery::query()->create([
                ...$delivery,
                'context' => $context !== [] ? $context : null,
                'sent_at' => now(),
            ]);
        } catch (QueryException $exception) {
            if ($this->isDuplicateKeyException($exception)) {
                return false;
            }

            throw $exception;
        }

        return true;
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return $sqlState === '23000' || $driverCode === 1062;
    }
}

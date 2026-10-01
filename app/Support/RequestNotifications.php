<?php

namespace App\Support;

use App\Models\DocumentRequest;
use App\Models\User;

class RequestNotifications
{
    /**
     * Pending citizen document requests for the office top bar.
     *
     * @return array{count: int, items: list<array{id: int, tracking: string, name: string, type: string, submitted: string}>}
     */
    public static function forUser(?User $user): array
    {
        if (! $user || ! in_array($user->role, ['admin', 'employee'], true)) {
            return ['count' => 0, 'items' => []];
        }

        $pending = DocumentRequest::query()->where('status', 'pending');

        return [
            'count' => (clone $pending)->count(),
            'items' => (clone $pending)
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (DocumentRequest $request) => [
                    'id' => $request->id,
                    'tracking' => $request->tracking_number,
                    'name' => $request->requester_name,
                    'type' => $request->request_type,
                    'submitted' => $request->created_at?->diffForHumans() ?? '',
                ])
                ->all(),
        ];
    }
}

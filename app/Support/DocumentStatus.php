<?php

namespace App\Support;

class DocumentStatus
{
    public static function label(string $status): string
    {
        return match ($status) {
            'draft'        => 'Draft',
            'pending'      => 'Pending',
            'under_review' => 'Under Review',
            'for_approval' => 'For Approval',
            'approved'     => 'Approved',
            'released'     => 'Released',
            'returned'     => 'Returned',
            'rejected'     => 'Disapproved',
            'archived'     => 'Archived',
            default        => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public static function fromFilter(?string $status): ?string
    {
        if (! $status) {
            return null;
        }

        $normalized = strtolower(str_replace(' ', '_', $status));

        return $normalized === 'disapproved' ? 'rejected' : $normalized;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Enums;

enum RuleEventType: string
{
    case Purchase = 'purchase';
    case Maintenance = 'maintenance';
    case ReferralRegistered = 'referral_registered';
    case ReferralPurchase = 'referral_purchase';
    case EventAttendance = 'event_attendance';
    case Manual = 'manual';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}

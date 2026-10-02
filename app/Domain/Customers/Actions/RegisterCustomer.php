<?php

declare(strict_types=1);

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\BrandMember;
use App\Domain\Customers\CiHasher;
use App\Domain\Customers\CustomerRecord;
use App\Domain\Customers\Enums\ClaimStatus;
use App\Domain\Customers\Enums\MemberStatus;
use App\Domain\Customers\Enums\UserRole;
use App\Domain\Imports\Enums\PendingEventStatus;
use App\Domain\Loyalty\Actions\AwardPoints;
use App\Domain\Loyalty\Enums\RuleEventType;
use App\Domain\Referrals\Enums\ReferralStatus;
use App\Domain\Referrals\Referral;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Registra un cliente en una marca y, si existe un cliente precargado con el
 * mismo CI, intenta reclamarlo.
 *
 * Seguridad (AGENTS.md §7): no se enlaza solo con el CI. Se exige que coincida
 * un segundo dato (telefono o correo). Si el CI existe pero el segundo dato no
 * coincide, la membresia queda en pending_review para revision del admin.
 */
final class RegisterCustomer
{
    public function __construct(
        private readonly CiHasher $hasher,
        private readonly AwardPoints $awardPoints,
    ) {}

    /**
     * @param  array{name:string,email:string,password:string,ci:string,phone?:?string,birth_date?:?string}  $data
     * @return array{user:User,member:BrandMember,record:?CustomerRecord,linked:bool,credited:int}
     */
    public function handle(Brand $brand, array $data, ?string $referralCode = null): array
    {
        return DB::transaction(function () use ($brand, $data, $referralCode): array {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'ci' => $this->hasher->normalize($data['ci']),
                'ci_hash' => $this->hasher->hash($data['ci']),
                'phone' => $data['phone'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'role' => UserRole::Customer,
            ]);

            $record = $this->findRecord($brand, $data['ci']);
            [$memberStatus, $linkRecord] = $this->claimDecision($record, $data);

            $member = BrandMember::query()->create([
                'user_id' => $user->getKey(),
                'brand_id' => $brand->getKey(),
                'status' => $memberStatus,
                'referral_code' => BrandMember::generateReferralCode($brand->slug),
                'joined_at' => now(),
            ]);

            if ($linkRecord && $record !== null) {
                $record->update([
                    'claim_status' => ClaimStatus::Claimed,
                    'claimed_by_user_id' => $user->getKey(),
                    'claimed_at' => now(),
                ]);
            } elseif ($record !== null) {
                $record->update(['claim_status' => ClaimStatus::PendingReview]);
            }

            $credited = $record !== null
                ? $this->creditPendingEvents($member, $record)
                : 0;

            $this->registerReferral($brand, $member, $referralCode);

            return [
                'user' => $user,
                'member' => $member,
                'record' => $record,
                'linked' => $linkRecord,
                'credited' => $credited,
            ];
        });
    }

    private function findRecord(Brand $brand, string $ci): ?CustomerRecord
    {
        return CustomerRecord::withoutGlobalScopes()
            ->where('brand_id', $brand->getKey())
            ->where('ci_hash', $this->hasher->hash($ci))
            ->first();
    }

    /**
     * @param  array{name:string,email:string,password:string,ci:string,phone?:?string,birth_date?:?string}  $data
     * @return array{0:MemberStatus,1:bool}
     */
    private function claimDecision(?CustomerRecord $record, array $data): array
    {
        if ($record === null) {
            return [MemberStatus::Active, false];
        }

        if ($record->claimed_by_user_id !== null) {
            return [MemberStatus::PendingReview, false];
        }

        $secondFactorOk = $this->secondFactorMatches($record, $data);

        return $secondFactorOk
            ? [MemberStatus::Active, true]
            : [MemberStatus::PendingReview, false];
    }

    /**
     * @param  array{name:string,email:string,password:string,ci:string,phone?:?string,birth_date?:?string}  $data
     */
    private function secondFactorMatches(CustomerRecord $record, array $data): bool
    {
        $phone = $this->normalizeFactor($data['phone'] ?? null);
        $email = $this->normalizeFactor($data['email'] ?? null);

        foreach ((array) config('loyalty.claim.second_factor', ['phone', 'email']) as $factor) {
            $stored = $this->normalizeFactor($factor === 'phone' ? $record->phone : $record->email);

            if ($stored === null) {
                continue;
            }

            if ($factor === 'phone' && $phone !== null && $phone === $stored) {
                return true;
            }

            if ($factor === 'email' && $email !== null && $email === $stored) {
                return true;
            }
        }

        return false;
    }

    private function normalizeFactor(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_strtolower(trim($value), 'UTF-8');

        return $value === '' ? null : preg_replace('/[^a-z0-9@.]/', '', $value);
    }

    private function creditPendingEvents(BrandMember $member, CustomerRecord $record): int
    {
        $credited = 0;

        $events = $record->pendingEvents()
            ->where('status', PendingEventStatus::Pending->value)
            ->orderBy('occurred_at')
            ->get();

        foreach ($events as $event) {
            $type = RuleEventType::tryFrom($event->event_type);

            if ($type === null) {
                continue;
            }

            $ledger = $this->awardPoints->handle(
                member: $member,
                eventType: $type,
                amount: $event->amount,
                occurredAt: $event->occurred_at,
                idempotencyKey: $event->external_id !== null
                    ? $type->value.':'.$member->brand_id.':'.$event->external_id
                    : 'pending:'.$event->getKey(),
                sourceType: 'pending_event',
                sourceId: (int) $event->getKey(),
                meta: ['pending_event_id' => $event->getKey()],
            );

            $event->update([
                'status' => PendingEventStatus::Credited,
                'credited_member_id' => $member->getKey(),
                'credited_at' => now(),
            ]);

            if ($ledger !== null) {
                $credited++;
            }
        }

        return $credited;
    }

    private function registerReferral(Brand $brand, BrandMember $member, ?string $referralCode): void
    {
        if ($referralCode === null || trim($referralCode) === '') {
            return;
        }

        $referrer = BrandMember::withoutGlobalScopes()
            ->where('brand_id', $brand->getKey())
            ->where('referral_code', mb_strtoupper(trim($referralCode)))
            ->first();

        // No auto-referirse y un solo referidor por referido y marca.
        if ($referrer === null || $referrer->getKey() === $member->getKey()) {
            return;
        }

        if (Referral::withoutGlobalScopes()->where('referred_member_id', $member->getKey())->exists()) {
            return;
        }

        Referral::withoutGlobalScopes()->create([
            'brand_id' => $brand->getKey(),
            'referrer_member_id' => $referrer->getKey(),
            'referred_member_id' => $member->getKey(),
            'status' => ReferralStatus::Registered,
            'registered_at' => now(),
        ]);

        $member->update(['referred_by_member_id' => $referrer->getKey()]);

        // El premio por referral_registered se implementa en la Fase 3.
    }
}

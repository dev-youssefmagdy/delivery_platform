<?php

namespace App\Enums\Provider;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Provider onboarding and standing (Plan §7.3). The graph below is the only source of allowed transitions;
 * `ProviderStatusService` is the only code that changes `provider_profiles.status`.
 *
 * `online_eligible` and `expired_documents` are deliberately NOT cases: they are computed by
 * `ProviderEligibilityService`, so they cannot drift from reality.
 */
enum ProviderStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Draft = 'draft';
    case PhoneVerified = 'phone_verified';
    case ProfileSubmitted = 'profile_submitted';
    case DocumentsPending = 'documents_pending';
    case UnderReview = 'under_review';
    case MoreInformationRequired = 'more_information_required';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case TemporarilySuspended = 'temporarily_suspended';
    case PermanentlySuspended = 'permanently_suspended';
    case DeletionPending = 'deletion_pending';

    public function label(): string
    {
        return __("enums.provider_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Approved => BadgeColor::Success,
            self::UnderReview => BadgeColor::Info,
            self::MoreInformationRequired, self::TemporarilySuspended, self::DeletionPending => BadgeColor::Warning,
            self::Rejected, self::PermanentlySuspended => BadgeColor::Danger,
            default => BadgeColor::Neutral,
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::PhoneVerified],
            self::PhoneVerified => [self::ProfileSubmitted, self::DeletionPending],
            self::ProfileSubmitted => [self::DocumentsPending, self::DeletionPending],
            self::DocumentsPending => [self::UnderReview, self::DeletionPending],
            self::UnderReview => [self::Approved, self::MoreInformationRequired, self::Rejected, self::DeletionPending],
            self::MoreInformationRequired => [self::UnderReview, self::Rejected, self::DeletionPending],
            self::Rejected => [self::UnderReview, self::DeletionPending],
            self::Approved => [self::TemporarilySuspended, self::PermanentlySuspended, self::DeletionPending],
            self::TemporarilySuspended => [self::Approved, self::PermanentlySuspended, self::DeletionPending],
            self::PermanentlySuspended => [self::DeletionPending],
            // A cancelled or rejected deletion request puts the provider back where it was.
            self::DeletionPending => [
                self::PhoneVerified, self::ProfileSubmitted, self::DocumentsPending, self::UnderReview, self::MoreInformationRequired,
                self::Rejected, self::Approved, self::TemporarilySuspended, self::PermanentlySuspended,
            ],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * The applicant may still change the application (profile, services) in these statuses.
     */
    public function isApplicationEditable(): bool
    {
        return in_array($this, [self::PhoneVerified, self::ProfileSubmitted, self::DocumentsPending, self::MoreInformationRequired, self::Rejected], true);
    }

    /**
     * Statuses in which the provider may upload documents and add or change vehicles (renewals included).
     */
    public function allowsDocumentsAndVehicles(): bool
    {
        return $this->isApplicationEditable() || in_array($this, [self::UnderReview, self::Approved, self::TemporarilySuspended], true);
    }

    public function isSuspended(): bool
    {
        return $this === self::TemporarilySuspended || $this === self::PermanentlySuspended;
    }

    public function canSubmitForReview(): bool
    {
        return in_array($this, [self::DocumentsPending, self::MoreInformationRequired, self::Rejected], true);
    }

    public function isAwaitingReview(): bool
    {
        return $this === self::UnderReview || $this === self::MoreInformationRequired;
    }
}

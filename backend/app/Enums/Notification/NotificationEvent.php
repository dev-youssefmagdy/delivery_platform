<?php

namespace App\Enums\Notification;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Every event that can notify a person (SRS §14.1). The case owns its category, audience, default channels, template
 * variables and what a tap opens, so templates, preferences and the delivery log all read the same facts. OTP is
 * deliberately not an event: it goes straight through the SMS gateway and is never stored or logged in clear text.
 */
enum NotificationEvent: string implements HasLabel
{
    use EnumHelpers;

    case ProviderApplicationSubmitted = 'provider.application_submitted';
    case ProviderApplicationMoreInfo = 'provider.application_more_info';
    case ProviderApplicationApproved = 'provider.application_approved';
    case ProviderApplicationRejected = 'provider.application_rejected';
    case ProviderSuspended = 'provider.suspended';
    case ProviderReactivated = 'provider.reactivated';
    case DocumentExpiring = 'document.expiring';
    case DocumentExpired = 'document.expired';

    case BookingSearchExpired = 'booking.search_expired';
    case BookingOfferReceived = 'booking.offer_received';
    case BookingOfferUpdated = 'booking.offer_updated';
    case BookingProviderSelected = 'booking.provider_selected';
    case BookingProviderHeading = 'booking.provider_heading';
    case BookingProviderArrived = 'booking.provider_arrived';
    case BookingTripStarted = 'booking.trip_started';
    case BookingCompleted = 'booking.completed';
    case BookingCancelled = 'booking.cancelled';
    case BookingCancellationFee = 'booking.cancellation_fee_applied';
    case BookingProviderCancelled = 'booking.provider_cancelled_redispatch';
    case BookingOperationsUpdate = 'booking.operations_update';
    case BookingOperationsContact = 'booking.operations_contact';
    case BookingAdjustmentRequested = 'booking.adjustment_requested';
    case BookingAdjustmentAnswered = 'booking.adjustment_answered';

    case RequestNew = 'request.new';
    case OfferAccepted = 'offer.accepted';
    case OfferRejected = 'offer.rejected';
    case OfferExpired = 'offer.expired';

    case PaymentPending = 'payment.pending';
    case PaymentSucceeded = 'payment.succeeded';
    case PaymentFailed = 'payment.failed';
    case PaymentRefunded = 'payment.refunded';
    case WalletAdjusted = 'wallet.adjusted';
    case CashDebtNotice = 'cash.debt_notice';

    case CouponApplied = 'coupon.applied';
    case CouponRejected = 'coupon.rejected';
    case CouponExpiring = 'coupon.expiring';

    case PayoutRequested = 'payout.requested';
    case PayoutApproved = 'payout.approved';
    case PayoutRejected = 'payout.rejected';
    case PayoutPaid = 'payout.paid';
    case PayoutFailed = 'payout.failed';

    case SupportHelpMessage = 'support.help_message';
    case SupportReply = 'support.reply';
    case SupportDisputeUpdate = 'support.dispute_update';
    case SafetyCaseUpdate = 'safety.case_update';

    case MarketingCampaign = 'marketing.campaign';

    case AdminExportReady = 'admin.export_ready';
    case AdminExportFailed = 'admin.export_failed';

    public function category(): NotificationCategory
    {
        return match ($this) {
            self::ProviderApplicationSubmitted, self::ProviderApplicationMoreInfo, self::ProviderApplicationApproved, self::ProviderApplicationRejected,
            self::ProviderSuspended, self::ProviderReactivated, self::DocumentExpiring, self::DocumentExpired => NotificationCategory::Account,
            self::BookingSearchExpired, self::BookingProviderSelected, self::BookingProviderHeading, self::BookingProviderArrived, self::BookingTripStarted,
            self::BookingCompleted, self::BookingCancelled, self::BookingProviderCancelled, self::BookingOperationsUpdate,
            self::BookingAdjustmentRequested, self::BookingAdjustmentAnswered, self::OfferAccepted => NotificationCategory::Booking,
            self::BookingOfferReceived, self::BookingOfferUpdated, self::RequestNew, self::OfferRejected, self::OfferExpired => NotificationCategory::Offers,
            self::BookingCancellationFee, self::PaymentPending, self::PaymentSucceeded, self::PaymentFailed, self::PaymentRefunded,
            self::WalletAdjusted, self::CashDebtNotice => NotificationCategory::Payments,
            self::CouponApplied, self::CouponRejected, self::CouponExpiring => NotificationCategory::Promotions,
            self::PayoutRequested, self::PayoutApproved, self::PayoutRejected, self::PayoutPaid, self::PayoutFailed => NotificationCategory::Payouts,
            self::BookingOperationsContact, self::SupportHelpMessage, self::SupportReply, self::SupportDisputeUpdate => NotificationCategory::Support,
            self::SafetyCaseUpdate => NotificationCategory::Safety,
            self::MarketingCampaign => NotificationCategory::Marketing,
            self::AdminExportReady, self::AdminExportFailed => NotificationCategory::System,
        };
    }

    public function audience(): NotificationAudience
    {
        return match ($this) {
            self::ProviderApplicationSubmitted, self::ProviderApplicationMoreInfo, self::ProviderApplicationApproved, self::ProviderApplicationRejected,
            self::ProviderSuspended, self::ProviderReactivated, self::DocumentExpiring, self::DocumentExpired, self::RequestNew,
            self::OfferAccepted, self::OfferRejected, self::OfferExpired, self::CashDebtNotice,
            self::PayoutRequested, self::PayoutApproved, self::PayoutRejected, self::PayoutPaid, self::PayoutFailed => NotificationAudience::Provider,
            self::BookingSearchExpired, self::BookingOfferReceived, self::BookingOfferUpdated, self::BookingProviderSelected, self::BookingProviderHeading,
            self::BookingProviderArrived, self::BookingTripStarted, self::BookingCompleted, self::BookingCancellationFee, self::BookingProviderCancelled,
            self::BookingOperationsContact, self::PaymentPending, self::PaymentSucceeded, self::PaymentFailed, self::PaymentRefunded,
            self::CouponApplied, self::CouponRejected, self::CouponExpiring, self::SupportHelpMessage => NotificationAudience::Customer,
            self::AdminExportReady, self::AdminExportFailed => NotificationAudience::Admin,
            default => NotificationAudience::Both,
        };
    }

    /**
     * Channels used before the person's preferences narrow them down. The inbox row is always written.
     *
     * @return list<NotificationChannel>
     */
    public function defaultChannels(): array
    {
        $channels = [NotificationChannel::InApp];

        if ($this->audience() !== NotificationAudience::Admin) {
            $channels[] = NotificationChannel::Push;
        }

        if (in_array($this, [
            self::ProviderApplicationApproved, self::ProviderApplicationRejected, self::ProviderApplicationMoreInfo, self::ProviderSuspended,
            self::BookingCancellationFee, self::PaymentFailed, self::PayoutPaid, self::PayoutFailed, self::DocumentExpired, self::SafetyCaseUpdate,
        ], true)) {
            $channels[] = NotificationChannel::Sms;
        }

        if (in_array($this, [
            self::ProviderApplicationApproved, self::ProviderApplicationRejected, self::ProviderApplicationMoreInfo, self::DocumentExpiring,
            self::DocumentExpired, self::PaymentRefunded, self::PayoutPaid, self::PayoutFailed, self::BookingCompleted, self::AdminExportReady,
        ], true)) {
            $channels[] = NotificationChannel::Email;
        }

        return $channels;
    }

    /**
     * @return list<string>
     */
    public function variables(): array
    {
        return match ($this) {
            self::DocumentExpiring => ['document', 'days'],
            self::DocumentExpired => ['document'],
            self::BookingAdjustmentAnswered => ['code', 'outcome'],
            self::PayoutRequested, self::PayoutApproved, self::PayoutRejected, self::PayoutPaid, self::PayoutFailed,
            self::PaymentRefunded, self::WalletAdjusted, self::CashDebtNotice => ['reference'],
            self::CouponApplied, self::CouponRejected, self::CouponExpiring => ['coupon'],
            self::MarketingCampaign => ['title', 'body'],
            self::AdminExportReady, self::AdminExportFailed => ['file'],
            self::ProviderApplicationSubmitted, self::ProviderApplicationMoreInfo, self::ProviderApplicationApproved, self::ProviderApplicationRejected,
            self::ProviderSuspended, self::ProviderReactivated, self::SupportHelpMessage, self::SupportReply, self::SupportDisputeUpdate,
            self::SafetyCaseUpdate, self::BookingOperationsContact => [],
            default => ['code'],
        };
    }

    public function linkType(): NotificationLinkType
    {
        return match ($this) {
            self::ProviderApplicationSubmitted, self::ProviderApplicationMoreInfo, self::ProviderApplicationApproved, self::ProviderApplicationRejected,
            self::ProviderSuspended, self::ProviderReactivated => NotificationLinkType::Application,
            self::DocumentExpiring, self::DocumentExpired => NotificationLinkType::Documents,
            self::PayoutRequested, self::PayoutApproved, self::PayoutRejected, self::PayoutPaid, self::PayoutFailed => NotificationLinkType::Payout,
            self::WalletAdjusted, self::CashDebtNotice, self::PaymentRefunded => NotificationLinkType::Wallet,
            self::SupportHelpMessage => NotificationLinkType::Message,
            self::SupportReply, self::SupportDisputeUpdate, self::SafetyCaseUpdate => NotificationLinkType::Support,
            self::CouponApplied, self::CouponRejected, self::CouponExpiring, self::MarketingCampaign, self::BookingOperationsContact => NotificationLinkType::Inbox,
            self::AdminExportReady, self::AdminExportFailed => NotificationLinkType::Export,
            default => NotificationLinkType::Booking,
        };
    }

    /**
     * Example values for the admin preview.
     *
     * @return array<string, string>
     */
    public function sampleVariables(): array
    {
        $samples = [
            'code' => 'BK-4F7A2C',
            'document' => __('admin.notification_templates.sample.document'),
            'days' => '7',
            'outcome' => __('admin.notification_templates.sample.outcome'),
            'reference' => 'REF-1042',
            'coupon' => 'WELCOME20',
            'title' => __('admin.notification_templates.sample.title'),
            'body' => __('admin.notification_templates.sample.body'),
            'file' => 'bookings-export.csv',
        ];

        return array_intersect_key($samples, array_flip($this->variables()));
    }

    public function label(): string
    {
        return __("enums.notification_event.{$this->value}");
    }

    /**
     * @return array<string, string> event value => label, grouped for selects
     */
    public static function optionsByCategory(): array
    {
        $options = [];

        foreach (self::cases() as $event) {
            $options[$event->value] = $event->label();
        }

        return $options;
    }
}

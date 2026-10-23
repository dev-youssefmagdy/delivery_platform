<?php

/*
| Built-in notification texts (P3.6). They seed `notification_templates` and are the fallback when a template is missing
| or inactive. Lock-screen texts are deliberately short and carry no names, addresses or amounts; details are in the app.
| Placeholders: {code} booking code, {document}, {days}, {reference}, {coupon}, {outcome}, {file}.
*/
return [
    'provider.application_submitted' => ['title' => 'Application received', 'body' => 'We received your application and will review it soon.'],
    'provider.application_more_info' => [
        'title' => 'More information needed', 'body' => 'Your application needs a few changes. Open the app to see what to update.',
        'sms' => 'Your driver application needs more information. Open the app to continue.',
        'email_subject' => 'More information needed for your application', 'email_body' => "Your driver application needs a few changes.\nOpen the app to see what to update and send it again.",
    ],
    'provider.application_approved' => [
        'title' => 'You are approved', 'body' => 'Your application was approved. You can go online now.',
        'sms' => 'Your driver application was approved. You can go online now.',
        'email_subject' => 'Your application was approved', 'email_body' => "Good news: your driver application was approved.\nOpen the app to go online and start receiving requests.",
    ],
    'provider.application_rejected' => [
        'title' => 'Application update', 'body' => 'We could not approve your application. Open the app for details.',
        'sms' => 'Your driver application was not approved. Open the app for details.',
        'email_subject' => 'Update on your application', 'email_body' => "We could not approve your driver application.\nOpen the app to see the reason.",
    ],
    'provider.suspended' => ['title' => 'Account update', 'body' => 'Your account is suspended. Open the app for details.', 'sms' => 'Your driver account is suspended. Open the app for details.'],
    'provider.reactivated' => ['title' => 'Account active again', 'body' => 'Your account is active again. You can go online.'],
    'document.expiring' => [
        'title' => 'Document expiring soon', 'body' => 'A document expires in {days} days. Upload a new one to keep driving.',
        'email_subject' => 'A document is about to expire', 'email_body' => "Your document \"{document}\" expires in {days} days.\nUpload a new one in the app to keep receiving requests.",
    ],
    'document.expired' => [
        'title' => 'Document expired', 'body' => 'A document expired. Upload a new one to go online again.',
        'sms' => 'A document on your driver account expired. Upload a new one in the app to go online again.',
        'email_subject' => 'A document expired', 'email_body' => "Your document \"{document}\" expired and you cannot go online until you upload a valid one.",
    ],

    'booking.search_expired' => ['title' => 'No driver found', 'body' => 'We could not find a driver for booking {code}. You can try again.'],
    'booking.offer_received' => ['title' => 'New offer', 'body' => 'A driver sent an offer for booking {code}.'],
    'booking.offer_updated' => ['title' => 'Offer updated', 'body' => 'A driver changed their offer for booking {code}.'],
    'booking.provider_selected' => ['title' => 'Driver confirmed', 'body' => 'Your driver for booking {code} is confirmed.'],
    'booking.provider_heading' => ['title' => 'Driver on the way', 'body' => 'Your driver is on the way.'],
    'booking.provider_arrived' => ['title' => 'Your driver arrived', 'body' => 'Your driver has arrived.'],
    'booking.trip_started' => ['title' => 'Trip started', 'body' => 'Your trip has started.'],
    'booking.message_received' => ['title' => 'New message', 'body' => 'You have a new message about booking {code}.'],
    'booking.completed' => [
        'title' => 'Trip completed', 'body' => 'Booking {code} is complete.',
        'email_subject' => 'Your trip is complete', 'email_body' => "Booking {code} is complete.\nYour receipt is available in the app.",
    ],
    'booking.cancelled' => ['title' => 'Booking cancelled', 'body' => 'Booking {code} was cancelled.'],
    'booking.cancellation_fee_applied' => [
        'title' => 'Cancellation fee', 'body' => 'A cancellation fee applies to booking {code}. Open the app for details.',
        'sms' => 'A cancellation fee applies to your booking {code}. Open the app for details.',
    ],
    'booking.provider_cancelled_redispatch' => ['title' => 'Looking for another driver', 'body' => 'Your driver cancelled. We are looking for another one for booking {code}.'],
    'booking.operations_update' => ['title' => 'Update on your booking', 'body' => 'Support updated booking {code}. Open the app for details.'],
    'booking.adjustment_requested' => ['title' => 'Fare change request', 'body' => 'A fare change was requested for booking {code}. Please respond.'],
    'booking.adjustment_answered' => ['title' => 'Fare change answered', 'body' => 'Fare change for booking {code}: {outcome}.'],

    'delivery.picked_up' => ['title' => 'Parcel picked up', 'body' => 'Your parcel for booking {code} was picked up.'],
    'delivery.arrived_dropoff' => ['title' => 'Parcel arriving', 'body' => 'The driver arrived at the drop-off for booking {code}.'],
    'delivery.delivered' => ['title' => 'Parcel delivered', 'body' => 'Your parcel for booking {code} was delivered.'],
    'delivery.attempt_failed' => ['title' => 'Delivery not completed', 'body' => 'The delivery for booking {code} could not be completed. Open the app to choose what happens next.'],
    'delivery.attempt_resolved' => ['title' => 'Delivery update', 'body' => 'A decision was made about the failed delivery of booking {code}. Open the app for details.'],
    'delivery.returned' => ['title' => 'Parcel returned', 'body' => 'Your parcel for booking {code} was returned to the sender.'],
    'delivery.recipient_otp_locked' => ['title' => 'Delivery code locked', 'body' => 'Too many wrong codes for booking {code}. Support was notified.'],

    'request.new' => ['title' => 'New request nearby', 'body' => 'A new request is available near you.'],
    'offer.accepted' => ['title' => 'Offer accepted', 'body' => 'The customer chose you for booking {code}.'],
    'offer.rejected' => ['title' => 'Offer declined', 'body' => 'The customer declined your offer for booking {code}.'],
    'offer.expired' => ['title' => 'Offer ended', 'body' => 'Your offer for booking {code} is no longer available.'],

    'payment.pending' => ['title' => 'Payment pending', 'body' => 'Your payment for booking {code} is being processed.'],
    'payment.succeeded' => ['title' => 'Payment received', 'body' => 'Your payment for booking {code} was received.'],
    'payment.failed' => [
        'title' => 'Payment failed', 'body' => 'Your payment for booking {code} did not go through. Open the app to try again.',
        'sms' => 'Your payment for booking {code} did not go through. Open the app to try again.',
    ],
    'payment.refunded' => [
        'title' => 'Refund issued', 'body' => 'A refund was issued for booking {code}.',
        'email_subject' => 'Your refund was issued', 'email_body' => "A refund was issued for booking {code}.\nIt can take a few days to appear with your bank.",
    ],
    'wallet.adjusted' => ['title' => 'Wallet updated', 'body' => 'Your wallet balance changed. Open the app for details.'],
    'cash.debt_notice' => ['title' => 'Amount due', 'body' => 'You have an amount due to the platform. Open the app for details.'],

    'coupon.applied' => ['title' => 'Coupon applied', 'body' => 'Coupon {coupon} was applied to your booking.'],
    'coupon.rejected' => ['title' => 'Coupon not applied', 'body' => 'Coupon {coupon} could not be applied.'],
    'coupon.expiring' => ['title' => 'Coupon expiring', 'body' => 'Coupon {coupon} expires soon.'],

    'payout.requested' => ['title' => 'Payout requested', 'body' => 'Your payout request was received.'],
    'payout.approved' => ['title' => 'Payout approved', 'body' => 'Your payout request was approved.'],
    'payout.rejected' => ['title' => 'Payout declined', 'body' => 'Your payout request was declined. Open the app for details.'],
    'payout.paid' => [
        'title' => 'Payout sent', 'body' => 'Your payout was sent.',
        'sms' => 'Your payout was sent. Open the app for details.',
        'email_subject' => 'Your payout was sent', 'email_body' => "Your payout was sent.\nOpen the app to see the details.",
    ],
    'payout.failed' => [
        'title' => 'Payout failed', 'body' => 'Your payout could not be sent. Check your payout method.',
        'sms' => 'Your payout could not be sent. Check your payout method in the app.',
        'email_subject' => 'Your payout could not be sent', 'email_body' => "Your payout could not be sent.\nCheck your payout method in the app.",
    ],

    'support.help_message' => ['title' => 'Message from support', 'body' => 'You have a new message from support.'],
    'support.reply' => ['title' => 'Support replied', 'body' => 'Support replied to your request.'],
    'support.dispute_update' => ['title' => 'Dispute update', 'body' => 'There is an update on your dispute.'],
    'safety.case_update' => [
        'title' => 'Safety case update', 'body' => 'There is an update on your safety case. Open the app.',
        'sms' => 'There is an update on your safety case. Open the app.',
    ],

    'safety.incident_raised' => ['title' => 'New safety case', 'body' => 'Safety case {code} needs a safety officer.'],

    'marketing.campaign' => ['title' => '{title}', 'body' => '{body}'],

    'admin.export_ready' => [
        'title' => 'Export ready', 'body' => 'Your export {file} is ready to download.',
    ],
    'admin.export_failed' => ['title' => 'Export failed', 'body' => 'Your export {file} could not be generated.'],

    // Words substituted for coded template variables, in this language.
    '_values' => [
        'outcome' => ['approved' => 'approved', 'declined' => 'declined', 'expired' => 'expired', 'voided' => 'cancelled by support', 'withdrawn' => 'withdrawn'],
    ],
];

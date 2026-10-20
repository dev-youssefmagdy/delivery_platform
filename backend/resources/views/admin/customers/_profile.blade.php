{{-- Profile tab body. Expects $details, $customer, $profile, $format (see show.blade.php). Contact values are masked. --}}
<x-admin.detail-list>
    <x-admin.detail-list.item :label="__('admin.customers.fields.uuid')"><span class="ltr-only font-mono text-xs">{{ $customer->uuid }}</span></x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.customers.fields.name')" :value="$customer->name" />
    <x-admin.detail-list.item :label="__('admin.customers.fields.phone')"><span class="ltr-only" data-reveal-field="phone">{{ $details->maskedPhone }}</span></x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.customers.fields.email')">
        @if ($details->maskedEmail)
            <span class="ltr-only" data-reveal-field="email">{{ $details->maskedEmail }}</span>
        @endif
    </x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.customers.fields.status')"><x-admin.badge :value="$customer->status" /></x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.customers.fields.locale')" :value="$customer->preferred_locale ? \App\Enums\Common\Locale::tryFrom($customer->preferred_locale)?->label() : null" />
    <x-admin.detail-list.item :label="__('admin.customers.fields.registered')" :value="$format($customer->created_at)" />
    <x-admin.detail-list.item :label="__('admin.customers.fields.phone_verified')" :value="$format($customer->phone_verified_at)" />
    <x-admin.detail-list.item :label="__('admin.customers.fields.last_seen')" :value="$format($customer->last_seen_at)" />
    <x-admin.detail-list.item :label="__('admin.customers.fields.marketing')">
        <x-admin.badge :label="$profile?->marketing_opt_in ? __('admin.common.yes') : __('admin.common.no')" :color="$profile?->marketing_opt_in ? 'success' : 'neutral'" />
    </x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.customers.fields.accessibility')" :value="$profile?->accessibility_needs" />
    <x-admin.detail-list.item :label="__('admin.customers.fields.emergency_contact')" :value="$profile?->emergency_contact_name" />
    <x-admin.detail-list.item :label="__('admin.customers.fields.emergency_phone')">
        @if ($details->maskedEmergencyPhone)
            <span class="ltr-only" data-reveal-field="emergency_contact_phone">{{ $details->maskedEmergencyPhone }}</span>
        @endif
    </x-admin.detail-list.item>
</x-admin.detail-list>
@unless ($details->canRevealContact)
    <p class="mt-4 text-xs text-slate-500">{{ __('admin.customers.reveal.no_permission') }}</p>
@endunless

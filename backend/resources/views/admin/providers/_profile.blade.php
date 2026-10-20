{{-- Profile tab body. Expects $details, $provider, $user, $maskedPhone, $format. Contact values are masked until revealed. --}}
<x-admin.detail-list>
    <x-admin.detail-list.item :label="__('admin.providers.fields.uuid')"><span class="ltr-only font-mono text-xs">{{ $provider->uuid }}</span></x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.providers.fields.name')" :value="$user->name" />
    <x-admin.detail-list.item :label="__('admin.providers.fields.phone')"><span class="ltr-only" data-reveal-field="phone">{{ $maskedPhone }}</span></x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.providers.fields.email')">
        @if ($user->email)
            <span class="ltr-only" data-reveal-field="email">{{ $user->email === null ? '' : mb_substr($user->email, 0, 1).'•••@'.\Illuminate\Support\Str::after($user->email, '@') }}</span>
        @endif
    </x-admin.detail-list.item>
    @if ($details->canRevealContact)
        <x-admin.detail-list.item :label="__('admin.providers.fields.date_of_birth')">
            @if ($provider->date_of_birth)
                <span class="ltr-only" data-reveal-field="date_of_birth">••••-••-••</span>
            @endif
        </x-admin.detail-list.item>
    @endif
    <x-admin.detail-list.item :label="__('admin.providers.fields.provider_type')" :value="$provider->provider_type?->label()" />
    <x-admin.detail-list.item :label="__('admin.providers.fields.status')"><x-admin.badge :value="$provider->status" /></x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.providers.fields.city')" :value="$provider->city?->name" />
    <x-admin.detail-list.item :label="__('admin.providers.fields.account_status')"><x-admin.badge :value="$user->status" /></x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.providers.fields.applied')" :value="$format($provider->created_at)" />
    <x-admin.detail-list.item :label="__('admin.providers.fields.submitted')" :value="$format($provider->submitted_at)" />
    <x-admin.detail-list.item :label="__('admin.providers.fields.approved_at')" :value="$format($provider->approved_at)" />
    <x-admin.detail-list.item :label="__('admin.providers.fields.online')">
        <x-admin.badge :label="$provider->is_online ? __('admin.providers.online') : __('admin.providers.offline')" :color="$provider->is_online ? 'success' : 'neutral'" />
    </x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.providers.fields.rating')" :value="$provider->rating_count > 0 ? $provider->rating_avg.' ('.$provider->rating_count.')' : null" />
    <x-admin.detail-list.item :label="__('admin.providers.fields.completed_jobs')" :value="$provider->completed_jobs" />
    <x-admin.detail-list.item :label="__('admin.providers.fields.vehicles')" :value="$details->vehicleCount" />
</x-admin.detail-list>

<h3 class="mt-6 mb-2 text-sm font-semibold text-slate-800">{{ __('admin.providers.eligibility.title') }}</h3>
@if ($details->eligibility->isEligible())
    <x-admin.alert type="success">{{ __('admin.providers.eligibility.eligible') }}</x-admin.alert>
@else
    <x-admin.alert type="warning" :title="__('admin.providers.eligibility.not_eligible')">
        <ul class="list-disc space-y-1 ps-5">
            @foreach ($details->eligibility->issues as $issue)
                <li>{{ $issue->reason->label() }}@if ($issue->reference) <span class="ltr-only text-xs text-slate-500">({{ $issue->reference }})</span>@endif</li>
            @endforeach
        </ul>
    </x-admin.alert>
@endif
@unless ($details->canRevealContact)
    <p class="mt-4 text-xs text-slate-500">{{ __('admin.providers.reveal.no_permission') }}</p>
@endunless

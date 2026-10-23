@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i') ?? '—';
    $title = __('growth.relationships.show_title');
@endphp
<x-admin.layouts.app :title="$title">
    <x-admin.page-header
        :title="$title"
        :subtitle="$relationship->campaign->displayName()"
        :breadcrumbs="[['label' => __('growth.relationships.title'), 'url' => route('admin.referral-relationships.index')], ['label' => $title]]"
    >
        @can('review', $relationship)
            <x-admin.confirm-action :url="route('admin.referral-relationships.approve', $relationship)" :title="__('growth.referral.confirm.approve_title')" :message="__('growth.referral.confirm.approve_message')" :confirm-label="__('growth.referral.actions.approve')" variant="secondary">{{ __('growth.referral.actions.approve') }}</x-admin.confirm-action>
            <x-admin.confirm-action :url="route('admin.referral-relationships.reject', $relationship)" :title="__('growth.referral.confirm.reject_title')" :message="__('growth.referral.confirm.reject_message')" :confirm-label="__('growth.referral.actions.reject')" variant="danger" :reason-required="true">{{ __('growth.referral.actions.reject') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($relationship->status === \App\Enums\Growth\RelationshipStatus::UnderReview)
        <x-admin.alert type="warning" class="mb-6">{{ __('growth.referral.review_notice') }}</x-admin.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card :title="__('admin.common.details')">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('growth.fields.status')"><x-admin.badge :value="$relationship->status" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('growth.fields.audience')"><x-admin.badge :value="$relationship->audience" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('growth.referral.fields.referrer')">
                        @if ($relationship->audience === \App\Enums\Growth\ReferralAudience::Customer && auth('admin')->user()?->can('customers.view'))
                            <a class="text-brand-700 hover:underline" href="{{ route('admin.customers.show', $relationship->referrer) }}">{{ $relationship->referrer->name }}</a>
                        @else {{ $relationship->referrer->name ?? '—' }} @endif
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('growth.referral.fields.referee')">
                        @if ($relationship->audience === \App\Enums\Growth\ReferralAudience::Customer && auth('admin')->user()?->can('customers.view'))
                            <a class="text-brand-700 hover:underline" href="{{ route('admin.customers.show', $relationship->referee) }}">{{ $relationship->referee->name }}</a>
                        @else {{ $relationship->referee->name ?? '—' }} @endif
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('growth.referral.fields.jobs')" :value="$relationship->jobs_completed.' / '.$relationship->campaign->milestone_jobs" />
                    <x-admin.detail-list.item :label="__('growth.fields.created')" :value="$when($relationship->created_at)" />
                    <x-admin.detail-list.item :label="__('growth.referral.fields.qualified_at')" :value="$when($relationship->qualified_at)" />
                    <x-admin.detail-list.item :label="__('growth.referral.fields.decided_at')" :value="$when($relationship->decided_at)" />
                    <x-admin.detail-list.item :label="__('growth.referral.fields.decision_reason')" :value="$relationship->decision_reason ?? '—'" />
                </x-admin.detail-list>
            </x-admin.card>

            <x-admin.card :title="__('growth.referral.flags_title')" :subtitle="__('growth.referral.flags_hint')">
                @forelse ($flags as $flag)
                    <div class="mb-3 rounded-(--radius-control) border border-slate-200 p-3">
                        <div class="font-medium text-slate-900">{{ $flag['label'] }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ collect($flag['evidence'])->map(fn ($v, $k) => \Illuminate\Support\Str::headline((string) $k).': '.(is_bool($v) ? ($v ? __('admin.common.yes') : __('admin.common.no')) : $v))->implode('، ') }}</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('growth.referral.no_flags') }}</p>
                @endforelse
            </x-admin.card>

            <x-admin.card :title="__('growth.rewards.title')">
                @forelse ($relationship->rewards as $reward)
                    <div class="flex items-center justify-between border-b border-slate-100 py-2 last:border-0">
                        <span><x-admin.badge :value="$reward->recipient" /> <x-admin.badge :value="$reward->status" /></span>
                        <x-admin.money :minor="$reward->amount_minor" :currency="$reward->currency" />
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('growth.rewards.none') }}</p>
                @endforelse
            </x-admin.card>

            <x-admin.card :title="__('admin.common.activity')">
                @include('admin.partials.activity', ['logs' => $logs])
            </x-admin.card>
        </div>
    </div>
</x-admin.layouts.app>

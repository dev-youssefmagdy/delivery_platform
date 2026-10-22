@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
@endphp
<x-admin.layouts.app :title="__('admin.push_campaigns.show_title')">
    <x-admin.page-header
        :title="$campaign->name"
        :subtitle="$campaign->audience->label()"
        :breadcrumbs="[['label' => __('admin.push_campaigns.title'), 'url' => route('admin.push-campaigns.index')], ['label' => $campaign->name]]"
    >
        @can('update', $campaign)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.push-campaigns.edit', $campaign)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('schedule', $campaign)
            <x-admin.confirm-action :url="route('admin.push-campaigns.schedule', ['pushCampaign' => $campaign, 'from_show' => 1])" :title="__('admin.push_campaigns.confirm.schedule_title')" :message="__('admin.push_campaigns.confirm.schedule_message', ['at' => $when($campaign->scheduled_at)])" :confirm-label="__('admin.push_campaigns.actions.schedule')" icon="calendar">{{ __('admin.push_campaigns.actions.schedule') }}</x-admin.confirm-action>
        @endcan
        @can('send', $campaign)
            <x-admin.confirm-action :url="route('admin.push-campaigns.send', ['pushCampaign' => $campaign, 'from_show' => 1])" :title="__('admin.push_campaigns.confirm.send_title')" :message="__('admin.push_campaigns.confirm.send_message', ['count' => $estimate ?? 0])" :confirm-label="__('admin.push_campaigns.actions.send')" reason-required variant="warning" icon="megaphone">{{ __('admin.push_campaigns.actions.send') }}</x-admin.confirm-action>
        @endcan
        @can('cancel', $campaign)
            <x-admin.confirm-action :url="route('admin.push-campaigns.cancel', ['pushCampaign' => $campaign, 'from_show' => 1])" :title="__('admin.push_campaigns.confirm.cancel_title')" :message="__('admin.push_campaigns.confirm.cancel_message')" :confirm-label="__('admin.push_campaigns.actions.cancel')" reason-required variant="danger" icon="x-circle">{{ __('admin.push_campaigns.actions.cancel') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($estimate !== null)
        <x-admin.alert type="info" class="mb-6">{{ __('admin.push_campaigns.estimate', ['count' => $estimate]) }}</x-admin.alert>
    @endif

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.push_campaigns.fields.status')"><x-admin.badge :value="$campaign->status" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.push_campaigns.fields.audience')" :value="$campaign->audience->label()" />
                    <x-admin.detail-list.item :label="__('admin.push_campaigns.fields.scheduled_at')" :value="$when($campaign->scheduled_at)" />
                    <x-admin.detail-list.item :label="__('admin.push_campaigns.fields.started_at')" :value="$when($campaign->started_at)" />
                    <x-admin.detail-list.item :label="__('admin.push_campaigns.fields.finished_at')" :value="$when($campaign->finished_at)" />
                    <x-admin.detail-list.item :label="__('admin.push_campaigns.fields.recipients')" :value="$campaign->recipients_count" />
                    <x-admin.detail-list.item :label="__('admin.push_campaigns.fields.skipped')" :value="$campaign->skipped_count" />
                    <x-admin.detail-list.item :label="__('admin.push_campaigns.fields.created_by')" :value="$campaign->createdBy?->name" />
                    <x-admin.detail-list.item :label="__('admin.common.created_at')" :value="$when($campaign->created_at)" />
                    @if ($campaign->cancel_reason)
                        <x-admin.detail-list.item :label="__('admin.push_campaigns.fields.cancel_reason')" :value="$campaign->cancel_reason" />
                    @endif
                </x-admin.detail-list>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach (\App\Enums\Common\Locale::cases() as $locale)
                        @php([$title, $body] = $campaign->contentFor($locale->value))
                        <div class="rounded-(--radius-control) border border-slate-200 p-4" lang="{{ $locale->value }}" dir="{{ $locale->direction() }}">
                            <p class="mb-3 text-xs font-semibold text-slate-500">{{ $locale->label() }}@if ($locale->value === 'en' && $campaign->title_en === null) · {{ __('admin.push_campaigns.falls_back') }}@endif</p>
                            <div class="rounded-xl bg-slate-900 p-3 text-slate-50">
                                <p class="text-sm font-semibold">{{ $title }}</p>
                                <p class="whitespace-pre-line text-sm text-slate-200">{{ $body }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="activity" :url="route('admin.push-campaigns.activity', $campaign)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>

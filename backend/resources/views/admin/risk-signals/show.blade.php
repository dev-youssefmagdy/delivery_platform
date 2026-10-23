@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
    $isProvider = $signal->subject_type === \App\Enums\Risk\RiskSubjectType::Provider;
    $evidence = collect($signal->evidence);
@endphp
<x-admin.layouts.app :title="__('reputation.admin.risk.show_title')">
    <x-admin.page-header
        :title="$signal->type->label()"
        :subtitle="$subject['name']"
        :breadcrumbs="[['label' => __('reputation.admin.risk.title'), 'url' => route('admin.risk-signals.index')], ['label' => $signal->type->label()]]"
    >
        @if ($subject['model'] !== null && $subject['url'] !== null)
            @can('view', $subject['model'])
                <x-admin.button variant="secondary" icon="user" :href="$subject['url']">{{ $isProvider ? __('reputation.admin.open_provider') : __('reputation.admin.open_customer') }}</x-admin.button>
            @endcan
        @endif
        @can('escalate', $signal)
            <x-admin.confirm-action :url="route('admin.risk-signals.escalate', $signal)" :title="__('reputation.admin.confirm.escalate_title')" :message="__('reputation.admin.confirm.escalate_message')" :confirm-label="__('reputation.enums.risk_action.escalate')" variant="warning" reason-required icon="chevron-up">{{ __('reputation.enums.risk_action.escalate') }}</x-admin.confirm-action>
        @endcan
        @can('dismiss', $signal)
            <x-admin.confirm-action :url="route('admin.risk-signals.dismiss', $signal)" :title="__('reputation.admin.confirm.dismiss_title')" :message="__('reputation.admin.confirm.dismiss_message')" :confirm-label="__('reputation.enums.risk_action.dismiss')" variant="secondary" reason-required icon="x-mark">{{ __('reputation.enums.risk_action.dismiss') }}</x-admin.confirm-action>
        @endcan
        @can('restrict', $signal)
            <x-admin.confirm-action :url="route('admin.risk-signals.restrict', $signal)" :title="__('reputation.admin.confirm.restrict_title')" :message="__('reputation.admin.confirm.restrict_message')" :confirm-label="__('reputation.enums.risk_action.restrict')" variant="warning" reason-required icon="lock-closed">{{ __('reputation.enums.risk_action.restrict') }}</x-admin.confirm-action>
        @endcan
        @can('suspend', $signal)
            <x-admin.button variant="danger" icon="pause" data-modal-open="risk-suspend">{{ __('reputation.enums.risk_action.suspend') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.alert type="info" class="mb-6">{{ __('reputation.admin.risk.internal_notice') }}</x-admin.alert>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card :title="__('reputation.admin.risk.evidence')" :subtitle="__('reputation.admin.risk.evidence_hint')">
                <x-admin.detail-list>
                    @foreach ($evidence as $key => $value)
                        <x-admin.detail-list.item :label="\Illuminate\Support\Str::headline((string) $key)">
                            {{ is_array($value) ? collect($value)->map(fn ($v, $k) => is_int($k) ? $v : $k.': '.$v)->implode(', ') : $value }}
                        </x-admin.detail-list.item>
                    @endforeach
                </x-admin.detail-list>
            </x-admin.card>

            <x-admin.card :title="__('admin.common.activity')">
                @include('admin.partials.activity', ['logs' => $logs])
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card :title="__('admin.common.details')">
                <x-admin.detail-list :cols="1">
                    <x-admin.detail-list.item :label="__('reputation.admin.fields.status')"><x-admin.badge :value="$signal->status" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('reputation.admin.fields.score')" :value="$signal->score.'/100'" />
                    <x-admin.detail-list.item :label="__('reputation.admin.fields.subject')">
                        @if ($subject['url'])
                            <a class="text-brand-700 hover:underline" href="{{ $subject['url'] }}">{{ $subject['name'] }}</a>
                        @else
                            {{ $subject['name'] }}
                        @endif
                        <x-admin.badge :value="$signal->subject_type" />
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('reputation.admin.fields.detected')" :value="$when($signal->first_detected_at)" />
                    <x-admin.detail-list.item :label="__('reputation.admin.fields.evaluated')" :value="$when($signal->last_evaluated_at)" />
                    @if ($signal->escalated_at)
                        <x-admin.detail-list.item :label="__('reputation.admin.fields.escalated_at')" :value="$when($signal->escalated_at)" />
                    @endif
                    @if ($signal->resolved_at)
                        <x-admin.detail-list.item :label="__('reputation.admin.fields.resolved_at')" :value="$when($signal->resolved_at)" />
                        <x-admin.detail-list.item :label="__('reputation.admin.fields.resolution')" :value="($signal->resolution_action?->label() ?? '—').' · '.$signal->resolution_reason" />
                    @endif
                </x-admin.detail-list>
            </x-admin.card>
        </div>
    </div>

    @can('suspend', $signal)
        <x-admin.action-modal id="risk-suspend" :title="__('reputation.admin.confirm.suspend_title')" :message="__('reputation.admin.confirm.suspend_message')" :action="route('admin.risk-signals.suspend', $signal)" :submit="__('reputation.enums.risk_action.suspend')" icon="pause">
            <x-admin.form.datetime name="expires_at" id="risk-suspend-expires" :label="__('admin.customers.fields.suspension_ends')" :hint="__('reputation.admin.confirm.suspend_expiry_hint')" :min="now()->setTimezone($tz)->format('Y-m-d H:i')" />
        </x-admin.action-modal>
    @endcan
</x-admin.layouts.app>

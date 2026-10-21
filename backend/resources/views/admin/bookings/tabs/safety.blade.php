@php($pin = $safety['pin'])
<div class="space-y-8">
    <section>
        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.safety.pin_title') }}</h3>
        <p class="mb-3 text-sm text-slate-500">{{ __('admin.bookings.safety.pin_hint') }}</p>
        <x-admin.detail-list :cols="3">
            <x-admin.detail-list.item :label="__('admin.bookings.safety.pin_required')">
                <x-admin.badge :label="$pin['required'] ? __('admin.common.yes') : __('admin.common.no')" :color="$pin['required'] ? 'info' : 'neutral'" />
            </x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.bookings.safety.pin_state')">
                @if ($pin['verified_at'])
                    <x-admin.badge :label="__('admin.bookings.safety.pin_verified')" color="success" />
                @elseif ($pin['locked_at'])
                    <x-admin.badge :label="__('admin.bookings.safety.pin_locked')" color="danger" />
                @elseif ($pin['required'])
                    <x-admin.badge :label="__('admin.bookings.safety.pin_pending')" color="warning" />
                @else
                    <x-admin.badge :label="__('admin.bookings.safety.pin_not_required')" color="neutral" />
                @endif
            </x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.bookings.safety.pin_attempts')" :value="$pin['required'] ? $pin['attempts'].' / '.$pin['max_attempts'] : null" />
            <x-admin.detail-list.item :label="__('admin.bookings.safety.pin_verified_at')" :value="$pin['verified_at']" />
            <x-admin.detail-list.item :label="__('admin.bookings.safety.pin_locked_at')" :value="$pin['locked_at']" />
            <x-admin.detail-list.item :label="__('admin.bookings.safety.support_flag')">
                @if ($safety['flag'])
                    <x-admin.badge :label="__('admin.bookings.flags.support')" color="warning" /> <span class="text-xs text-slate-500">{{ $safety['flag']['at'] }}</span>
                @endif
            </x-admin.detail-list.item>
        </x-admin.detail-list>
    </section>

    <section>
        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.safety.proofs_title') }}</h3>
        @if ($safety['proofs'] === [])
            <x-admin.empty-state icon="photo" :title="__('admin.bookings.safety.no_proofs')" compact />
        @else
            <ul class="divide-y divide-slate-100 rounded-(--radius-control) border border-slate-200">
                @foreach ($safety['proofs'] as $proof)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-sm">
                        <span class="font-medium text-slate-900">{{ $proof['type'] }}</span>
                        <span class="text-slate-500">{{ $proof['actor'] }} · {{ $proof['time'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section>
        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.safety.reports_title') }}</h3>
        @if ($safety['reports'] === [])
            <x-admin.empty-state icon="shield" :title="__('admin.bookings.safety.no_reports')" compact />
        @else
            <ul class="divide-y divide-slate-100 rounded-(--radius-control) border border-slate-200">
                @foreach ($safety['reports'] as $report)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-sm">
                        <span class="font-medium text-slate-900">{{ $report['category'] }}</span>
                        <span class="text-slate-500">{{ $report['reporter'] }} · {{ $report['status'] }} · {{ $report['time'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>

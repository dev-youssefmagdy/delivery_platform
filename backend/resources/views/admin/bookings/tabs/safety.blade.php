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

    @if ($safety['delivery'])
        @php($delivery = $safety['delivery'])
        <section>
            <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.safety.delivery_title') }}</h3>
            <x-admin.detail-list :cols="3">
                <x-admin.detail-list.item :label="__('admin.bookings.safety.custody')" :value="$delivery['custody'] ?? '—'" />
                <x-admin.detail-list.item :label="__('admin.bookings.safety.restricted_goods')" :value="$delivery['restricted_goods_at'] ?? '—'" />
                @if ($delivery['otp'])
                    <x-admin.detail-list.item :label="__('admin.bookings.safety.otp_state')">
                        @if ($delivery['otp']['verified'])
                            <x-admin.badge :label="__('admin.bookings.safety.pin_verified')" color="success" />
                        @elseif ($delivery['otp']['locked'])
                            <x-admin.badge :label="__('admin.bookings.safety.pin_locked')" color="danger" />
                        @elseif ($delivery['otp']['issued'])
                            <x-admin.badge :label="__('admin.bookings.safety.pin_pending')" color="warning" />
                        @else
                            <x-admin.badge :label="__('admin.bookings.safety.otp_not_issued')" color="neutral" />
                        @endif
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.bookings.safety.otp_delivery')" :value="$delivery['otp']['delivery_status'] ? \App\Enums\Booking\RecipientOtpDelivery::from($delivery['otp']['delivery_status'])->label() : '—'" />
                    <x-admin.detail-list.item :label="__('admin.bookings.safety.pin_attempts')" :value="$delivery['otp']['max_attempts'] ? $delivery['otp']['attempts_used'].' / '.$delivery['otp']['max_attempts'] : null" />
                @endif
            </x-admin.detail-list>
            <h4 class="mb-2 mt-5 text-sm font-semibold text-slate-800">{{ __('admin.bookings.safety.attempts_title') }}</h4>
            @if ($delivery['attempts'] === [])
                <x-admin.empty-state icon="photo" :title="__('admin.bookings.safety.no_attempts')" compact />
            @else
                <ul class="divide-y divide-slate-100 rounded-(--radius-control) border border-slate-200">
                    @foreach ($delivery['attempts'] as $attempt)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-sm">
                            <span class="font-medium text-slate-900">#{{ $attempt['number'] }} · {{ $attempt['outcome'] }}@if ($attempt['reason']) <span class="font-normal text-slate-500">· {{ $attempt['reason'] }}</span>@endif</span>
                            <span class="text-slate-500">{{ $attempt['resolution'] ?? __('admin.bookings.safety.attempt_pending') }} · {{ $attempt['time'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    <section>
        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.safety.proofs_title') }}</h3>
        @if ($safety['proofs'] === [])
            <x-admin.empty-state icon="photo" :title="__('admin.bookings.safety.no_proofs')" compact />
        @else
            <ul class="divide-y divide-slate-100 rounded-(--radius-control) border border-slate-200">
                @foreach ($safety['proofs'] as $proof)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-sm">
                        <span class="font-medium text-slate-900">{{ $proof['type'] }} <span class="text-xs font-normal text-slate-500">· {{ $proof['stage'] }}</span>@if ($proof['detail']) <span class="text-xs font-normal text-slate-500">· {{ $proof['detail'] }}</span>@endif</span>
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

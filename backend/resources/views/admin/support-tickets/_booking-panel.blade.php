{{-- Linked booking: the facts an agent needs next to the thread. No phone numbers or addresses (those need bookings.view_pii on the booking page). --}}
@if ($booking !== null)
    <x-admin.card :title="__('admin.support_tickets.booking.title')">
        <x-admin.detail-list>
            <x-admin.detail-list.item :label="__('admin.support_tickets.fields.booking')">
                @can('view', $booking)
                    <a class="font-medium text-brand-700 hover:underline" href="{{ route('admin.bookings.show', $booking->uuid) }}">{{ $booking->code }}</a>
                @else
                    {{ $booking->code }}
                @endcan
            </x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.support_tickets.booking.status')"><x-admin.badge :value="$booking->status" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.support_tickets.booking.customer')" :value="$booking->customer?->name" />
            <x-admin.detail-list.item :label="__('admin.support_tickets.booking.provider')" :value="$booking->provider?->user?->name" />
            <x-admin.detail-list.item :label="__('admin.support_tickets.booking.fare')">
                <x-admin.money :minor="$booking->final_fare_minor ?? $booking->agreed_fare_minor" :currency="$booking->currency" />
            </x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.support_tickets.booking.payment')" :value="$booking->payment_status->label()" />
        </x-admin.detail-list>
        @if ($ticket->source === \App\Enums\Support\TicketSource::Appeal && ! empty($ticket->context['appeal']))
            <div class="mt-4 rounded-(--radius-control) border border-slate-200 bg-slate-50 p-3 text-sm">
                <p class="mb-1 font-semibold text-slate-800">{{ __('admin.support_tickets.booking.appeal_snapshot') }}</p>
                <p class="text-slate-700">
                    {{ __('admin.support_tickets.booking.appeal_fee') }}:
                    <x-admin.money :minor="(int) ($ticket->context['appeal']['fee_minor'] ?? 0)" :currency="(string) ($ticket->context['appeal']['currency'] ?? $booking->currency)" />
                </p>
                <p class="text-slate-600">{{ __('admin.support_tickets.booking.appeal_stage') }}: {{ $ticket->context['appeal']['decision']['stage'] ?? '—' }} · {{ $ticket->context['appeal']['decision']['basis'] ?? '—' }}</p>
            </div>
        @endif
    </x-admin.card>
@endif

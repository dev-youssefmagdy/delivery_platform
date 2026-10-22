{{-- Linked financial operations. The ticket only LINKS: refunds and adjustments are requested from the finance screens with their
     own permissions; resolving or closing a ticket never changes money. --}}
@php($canSeePayments = auth('admin')->user()?->can(\App\Enums\Admin\AdminPermission::PaymentsView->value))
@if ($canSeePayments || $ticket->payment_id || $ticket->payout_request_id)
    <x-admin.card :title="__('admin.support_tickets.finance.title')" :subtitle="__('admin.support_tickets.finance.subtitle')">
        @if ($payments->isEmpty() && ! $ticket->payout)
            <p class="text-sm text-slate-500">{{ __('admin.support_tickets.finance.none') }}</p>
        @endif
        <ul class="divide-y divide-slate-100">
            @foreach ($payments as $payment)
                <li class="flex items-center justify-between gap-3 py-2 text-sm">
                    <span class="min-w-0">
                        @can('view', $payment)
                            <a class="font-medium text-brand-700 hover:underline" href="{{ route('admin.payments.show', $payment) }}">{{ $payment->kind->label() }}</a>
                        @else
                            {{ $payment->kind->label() }}
                        @endcan
                        <span class="text-slate-500">· {{ $payment->status->label() }}</span>
                        @if ($ticket->payment_id === $payment->id)
                            <x-admin.badge :label="__('admin.support_tickets.finance.linked')" color="info" />
                        @endif
                    </span>
                    <x-admin.money :value="$payment->amount" />
                </li>
            @endforeach
            @if ($ticket->payout)
                <li class="flex items-center justify-between gap-3 py-2 text-sm">
                    <span>
                        @can('view', $ticket->payout)
                            <a class="font-medium text-brand-700 hover:underline" href="{{ route('admin.payout-requests.show', $ticket->payout) }}">{{ __('admin.support_tickets.fields.payout') }}</a>
                        @else
                            {{ __('admin.support_tickets.fields.payout') }}
                        @endcan
                        <x-admin.badge :label="__('admin.support_tickets.finance.linked')" color="info" />
                    </span>
                </li>
            @endif
        </ul>
        @can('linkFinance', $ticket)
            @if ($payments->isNotEmpty())
                <x-admin.form :action="route('admin.support-tickets.link-finance', $ticket)" method="POST" class="mt-4">
                    <x-admin.form.select name="payment_id" :label="__('admin.support_tickets.finance.link_payment')" :options="$payments->mapWithKeys(fn ($payment) => [$payment->uuid => $payment->kind->label().' · '.$payment->status->label()])->all()" :value="$ticket->payment?->uuid" required />
                    <x-admin.form.actions :submit="__('admin.support_tickets.finance.link')" class="mt-3 pt-3" />
                </x-admin.form>
            @endif
        @endcan
        <p class="mt-3 text-xs text-slate-500">{{ __('admin.support_tickets.finance.no_money_note') }}</p>
    </x-admin.card>
@endif

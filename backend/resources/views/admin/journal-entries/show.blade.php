@php
    $money = app(\App\Support\Money\MoneyFormatter::class);
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i:s');
    $totals = [];
    foreach ($lines as $line) {
        $totals[$line->currency] ??= ['debit' => 0, 'credit' => 0];
        $totals[$line->currency][$line->side->value] += $line->amount_minor;
    }
@endphp
<x-admin.layouts.app :title="__('admin.ledger.entries.show_title')">
    <x-admin.page-header
        :title="$entry->type->label()"
        :subtitle="$entry->uuid"
        :breadcrumbs="[['label' => __('admin.ledger.entries.title'), 'url' => route('admin.journal-entries.index')], ['label' => '#'.$entry->id]]"
    />

    @if ($entry->reversalOf)
        <x-admin.alert type="warning" class="mb-6">
            {{ __('admin.ledger.entries.reverses') }}
            <a href="{{ route('admin.journal-entries.show', $entry->reversalOf) }}" class="font-medium underline">{{ \Illuminate\Support\Str::substr($entry->reversalOf->uuid, 0, 8) }}</a>
        </x-admin.alert>
    @endif
    @if ($entry->reversedBy)
        <x-admin.alert type="warning" class="mb-6">
            {{ __('admin.ledger.entries.reversed_by') }}
            <a href="{{ route('admin.journal-entries.show', $entry->reversedBy) }}" class="font-medium underline">{{ \Illuminate\Support\Str::substr($entry->reversedBy->uuid, 0, 8) }}</a>
        </x-admin.alert>
    @endif

    <x-admin.card class="mb-6">
        <x-admin.detail-list>
            <x-admin.detail-list.item :label="__('admin.ledger.entries.fields.type')"><x-admin.badge :value="$entry->type" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.ledger.entries.fields.description')" :value="__('admin.'.$entry->description_key)" />
            <x-admin.detail-list.item :label="__('admin.ledger.entries.fields.posted')" :value="$when($entry->posted_at)" />
            <x-admin.detail-list.item :label="__('admin.ledger.entries.fields.reference')">
                @if ($entry->reference_type === 'booking' && $booking !== null)
                    @can('view', $booking)
                        <a href="{{ route('admin.bookings.show', $booking) }}" class="ltr-only text-brand-700 hover:underline">{{ $entry->meta['booking'] ?? ('#'.$entry->reference_id) }}</a>
                    @else
                        <span class="ltr-only">{{ $entry->meta['booking'] ?? ('#'.$entry->reference_id) }}</span>
                    @endcan
                @elseif ($entry->reference_type)
                    {{ $entry->reference_type }} #{{ $entry->reference_id }}
                @endif
            </x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.ledger.entries.fields.idempotency_key')"><span class="ltr-only break-all">{{ $entry->idempotency_key }}</span></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.ledger.entries.fields.created_by')" :value="$entry->created_by_type ? \App\Enums\Common\ActorType::tryFrom($entry->created_by_type)?->label() : null" />
            @if (! empty($entry->meta['reason']))
                <x-admin.detail-list.item :label="__('admin.ledger.entries.fields.reason')" :value="$entry->meta['reason']" full />
            @endif
        </x-admin.detail-list>
    </x-admin.card>

    <x-admin.card :padding="false" class="p-5">
        <h3 class="mb-4 text-sm font-semibold text-slate-800">{{ __('admin.ledger.entries.lines') }}</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-start text-xs uppercase text-slate-500">
                        <th class="py-2 pe-4 text-start font-medium">{{ __('admin.ledger.entries.line.account') }}</th>
                        <th class="py-2 pe-4 text-end font-medium">{{ __('admin.ledger.entries.line.debit') }}</th>
                        <th class="py-2 pe-4 text-end font-medium">{{ __('admin.ledger.entries.line.credit') }}</th>
                        <th class="py-2 text-end font-medium">{{ __('admin.ledger.entries.line.balance_after') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($lines as $line)
                        @php($isDebit = $line->side === \App\Enums\Finance\EntrySide::Debit)
                        <tr>
                            <td class="py-2.5 pe-4">
                                @can('view', $line->account)
                                    <a href="{{ route('admin.ledger-accounts.show', $line->account) }}" class="text-brand-700 hover:underline">{{ $line->account->type->label() }}</a>
                                @else
                                    {{ $line->account->type->label() }}
                                @endcan
                                <span class="ltr-only ms-1 text-xs text-slate-400">{{ \Illuminate\Support\Str::substr($line->account->uuid, 0, 8) }}</span>
                            </td>
                            <td class="py-2.5 pe-4 text-end tabular-nums" dir="ltr">{{ $isDebit ? $money->format($line->amount) : '' }}</td>
                            <td class="py-2.5 pe-4 text-end tabular-nums" dir="ltr">{{ ! $isDebit ? $money->format($line->amount) : '' }}</td>
                            <td class="py-2.5 text-end tabular-nums text-slate-600" dir="ltr">{{ $money->format(\App\Support\Money\Money::of($line->balance_after_minor, $line->amount->currency)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @foreach ($totals as $currency => $sum)
                        @php($cur = app(\App\Support\Money\CurrencyRegistry::class)->get($currency))
                        <tr class="border-t-2 border-slate-200 font-semibold">
                            <td class="py-2.5 pe-4">{{ __('admin.ledger.entries.line.total') }} ({{ $currency }})</td>
                            <td class="py-2.5 pe-4 text-end tabular-nums" dir="ltr">{{ $money->format(\App\Support\Money\Money::of($sum['debit'], $cur)) }}</td>
                            <td class="py-2.5 pe-4 text-end tabular-nums" dir="ltr">{{ $money->format(\App\Support\Money\Money::of($sum['credit'], $cur)) }}</td>
                            <td class="py-2.5 text-end">{!! $sum['debit'] === $sum['credit'] ? '<span class="text-success-700">'.e(__('admin.ledger.entries.line.balanced')).'</span>' : '<span class="text-danger-700">'.e(__('admin.ledger.entries.line.unbalanced')).'</span>' !!}</td>
                        </tr>
                    @endforeach
                </tfoot>
            </table>
        </div>
        <x-admin.alert type="info" class="mt-6">{{ __('admin.ledger.entries.immutable_notice') }}</x-admin.alert>
    </x-admin.card>
</x-admin.layouts.app>

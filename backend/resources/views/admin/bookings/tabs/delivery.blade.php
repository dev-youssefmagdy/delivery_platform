@php
    /** @var array<string, mixed> $delivery */
    /** @var \App\Models\Booking $booking */
    $attempts = $delivery['attempts'];
    $scope = $delivery['scope'];
    $load = $delivery['load'];
    $form = static fn (string $name): string => route('admin.bookings.override-form', ['booking' => $booking, 'form' => $name]);
@endphp
<div class="space-y-8">
    <section>
        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.delivery.contacts_title') }}</h3>
        @if ($delivery['contacts'] === [])
            <x-admin.empty-state icon="user" :title="__('admin.bookings.delivery.no_contacts')" compact />
        @else
            @if ($canViewPii)
                <x-admin.reveal-fields :url="route('admin.bookings.contacts.reveal', $booking)" :label="__('admin.bookings.delivery.reveal')">
                    @include('admin.bookings.tabs._delivery-contacts', ['contacts' => $delivery['contacts']])
                </x-admin.reveal-fields>
                <p class="mt-2 text-xs text-slate-500">{{ __('admin.bookings.delivery.reveal_hint') }}</p>
            @else
                @include('admin.bookings.tabs._delivery-contacts', ['contacts' => $delivery['contacts']])
            @endif
        @endif
    </section>

    @if ($load)
        <section>
            <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.delivery.items_title') }}</h3>
            <x-admin.detail-list :cols="3">
                <x-admin.detail-list.item :label="__('admin.bookings.delivery.weight')" :value="$load['weight_kg'] === null ? null : $load['weight_kg'].' kg'" />
                <x-admin.detail-list.item :label="__('admin.bookings.delivery.volume')" :value="$load['volume_m3'] === null ? null : $load['volume_m3'].' m³'" />
                <x-admin.detail-list.item :label="__('admin.bookings.delivery.declared_value')">
                    @if ($load['declared_value_minor'] !== null)<x-admin.money :minor="$load['declared_value_minor']" :currency="$booking->currency" />@endif
                </x-admin.detail-list.item>
                <x-admin.detail-list.item :label="__('admin.bookings.delivery.pickup_window')" :value="$load['pickup_window'] ? \Illuminate\Support\Carbon::parse($load['pickup_window']['start'])->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i').($load['pickup_window']['end'] ? ' → '.\Illuminate\Support\Carbon::parse($load['pickup_window']['end'])->setTimezone(config('admin.display_timezone'))->format('H:i') : '') : null" />
            </x-admin.detail-list>
            @if ($load['items'] !== [])
                <ul class="mt-3 divide-y divide-slate-100 rounded-(--radius-control) border border-slate-200">
                    @foreach ($load['items'] as $item)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-sm">
                            <span class="font-medium text-slate-900">
                                {{ $item['label'] ?? ($item['code'] ? __('admin.bookings.delivery.item_hidden') : '—') }} × {{ $item['quantity'] }}
                                @if ($item['note'])<span class="font-normal text-slate-500">· {{ $item['note'] }}</span>@endif
                            </span>
                            <span class="text-slate-500">
                                @if ($item['dimensions_cm']){{ $item['dimensions_cm']['length'] }}×{{ $item['dimensions_cm']['width'] }}×{{ $item['dimensions_cm']['height'] }} cm @endif
                                @if ($item['weight_kg'])· {{ $item['weight_kg'] }} kg @endif
                                @if ($item['volume_m3'])· {{ $item['volume_m3'] }} m³ @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    @if ($scope)
        <section>
            <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.delivery.scope_title') }}</h3>
            <p class="mb-3 text-sm text-slate-500">{{ __('admin.bookings.delivery.scope_hint') }}</p>
            <div class="overflow-x-auto rounded-(--radius-control) border border-slate-200">
                <table class="w-full text-start text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500">
                        <tr>
                            <th class="px-4 py-2 text-start font-medium"></th>
                            <th class="px-4 py-2 text-start font-medium">{{ __('admin.bookings.delivery.requested') }}</th>
                            <th class="px-4 py-2 text-start font-medium">{{ __('admin.bookings.delivery.agreed') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($scope['rows'] as $row)
                            <tr @class(['bg-amber-50' => $row['changed']])>
                                <td class="px-4 py-2 font-medium text-slate-800">{{ $row['label'] }}</td>
                                <td class="px-4 py-2 text-slate-600">{{ $row['requested'] ?? '—' }}</td>
                                <td class="px-4 py-2 text-slate-900">{{ $row['agreed'] ?? '—' }}@if ($row['changed']) <x-admin.badge :label="__('admin.bookings.delivery.changed')" color="warning" />@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @unless ($scope['has_agreed'])
                <p class="mt-2 text-xs text-slate-500">{{ __('admin.bookings.delivery.no_agreed_scope') }}</p>
            @endunless
        </section>
    @endif

    <section>
        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.delivery.custody_title') }}</h3>
        <p class="mb-3 text-sm text-slate-600">{{ __('admin.bookings.safety.custody') }}: <span class="font-medium text-slate-900">{{ $delivery['custody']['status'] ?? '—' }}</span></p>
        @if ($delivery['custody']['events'] === [])
            <x-admin.empty-state icon="clock" :title="__('admin.bookings.delivery.no_custody')" compact />
        @else
            <ol class="space-y-2 border-s border-slate-200 ps-4">
                @foreach ($delivery['custody']['events'] as $event)
                    <li class="text-sm"><span class="font-medium text-slate-900">{{ $event['title'] }}</span> <span class="text-slate-500">· {{ $event['actor'] }} · {{ $event['time'] }}</span></li>
                @endforeach
            </ol>
        @endif
    </section>

    <section>
        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.delivery.proofs_title') }}</h3>
        @if ($delivery['proofs'] === [])
            <x-admin.empty-state icon="photo" :title="__('admin.bookings.safety.no_proofs')" compact />
        @else
            @unless ($delivery['can_view_files'])
                <x-admin.alert type="info" class="mb-3">{{ __('admin.bookings.delivery.files_need_pii') }}</x-admin.alert>
            @endunless
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($delivery['proofs'] as $proof)
                    <figure class="overflow-hidden rounded-(--radius-control) border border-slate-200 bg-white">
                        @if ($proof['url'])
                            <a href="{{ $proof['url'] }}" target="_blank" rel="noopener noreferrer"><img src="{{ $proof['url'] }}" alt="{{ $proof['type'] }}" loading="lazy" class="h-44 w-full bg-slate-50 object-contain"></a>
                        @elseif ($proof['file_removed'])
                            <div class="flex h-44 items-center justify-center bg-slate-50 text-xs text-slate-500">{{ __('admin.bookings.safety.file_removed') }}</div>
                        @elseif ($proof['checklist'] !== null)
                            <div class="h-44 overflow-auto bg-slate-50 p-3 text-xs text-slate-600">
                                <p class="mb-1 font-medium">{{ __('admin.bookings.safety.checklist_summary', ['done' => count($proof['checklist']), 'total' => $proof['checklist_total']]) }}</p>
                                @foreach ($proof['checklist'] as $code)<p>✓ {{ $code }}</p>@endforeach
                            </div>
                        @elseif ($proof['weight_kg'] !== null)
                            <div class="flex h-44 flex-col items-center justify-center gap-1 bg-slate-50 text-sm text-slate-700">{{ $proof['weight_kg'] }} kg @if ($proof['weight_mismatch'])<x-admin.badge :label="__('admin.bookings.safety.weight_mismatch')" color="warning" />@endif</div>
                        @else
                            <div class="flex h-44 items-center justify-center bg-slate-50 text-xs text-slate-500">{{ $delivery['can_view_files'] ? $proof['type'] : __('admin.bookings.delivery.hidden') }}</div>
                        @endif
                        <figcaption class="px-3 py-2 text-xs text-slate-600"><span class="font-medium text-slate-900">{{ $proof['type'] }}</span> · {{ $proof['stage'] }}<br>{{ $proof['actor'] }} · {{ $proof['time'] }}</figcaption>
                    </figure>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ __('admin.bookings.delivery.audit_note') }}</p>
        @endif
    </section>

    <section>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-800">{{ __('admin.bookings.safety.attempts_title') }} <span class="font-normal text-slate-500">({{ $attempts['count'] }} / {{ $attempts['max'] }})</span></h3>
            @if ($attempts['pending'] && $canManage)
                <x-admin.button variant="warning" size="sm" icon="bolt" data-modal-open="admin-global-modal" :data-modal-url="$form('resolve_delivery_attempt')">{{ __('admin.bookings.actions.resolve_delivery_attempt') }}</x-admin.button>
            @endif
        </div>
        @if ($attempts['items'] === [])
            <x-admin.empty-state icon="photo" :title="__('admin.bookings.safety.no_attempts')" compact />
        @else
            <ul class="divide-y divide-slate-100 rounded-(--radius-control) border border-slate-200">
                @foreach ($attempts['items'] as $attempt)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-sm">
                        <span class="font-medium text-slate-900">#{{ $attempt['number'] }} · {{ $attempt['outcome'] }}@if ($attempt['reason']) <span class="font-normal text-slate-500">· {{ $attempt['reason'] }}</span>@endif</span>
                        <span class="text-slate-500">
                            {{ $attempt['resolution'] ?? __('admin.bookings.safety.attempt_pending') }}@if ($attempt['resolved_by']) ({{ $attempt['resolved_by'] }}, {{ $attempt['resolved_at'] }})@endif · {{ $attempt['time'] }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section>
        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.delivery.attachments_title') }}</h3>
        @if ($delivery['attachments'] === [])
            <x-admin.empty-state icon="photo" :title="__('admin.bookings.delivery.no_attachments')" compact />
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($delivery['attachments'] as $file)
                    <figure class="overflow-hidden rounded-(--radius-control) border border-slate-200 bg-white">
                        @if ($file['url'] && $file['kind'] === 'photo')
                            <a href="{{ $file['url'] }}" target="_blank" rel="noopener noreferrer"><img src="{{ $file['url'] }}" alt="" loading="lazy" class="h-32 w-full bg-slate-50 object-cover"></a>
                        @elseif ($file['url'])
                            <a href="{{ $file['url'] }}" target="_blank" rel="noopener noreferrer" class="flex h-32 items-center justify-center bg-slate-50 text-sm text-primary-700 underline">{{ __('admin.bookings.delivery.open_file') }}</a>
                        @else
                            <div class="flex h-32 items-center justify-center bg-slate-50 text-xs text-slate-500">{{ __('admin.bookings.delivery.hidden') }}</div>
                        @endif
                        <figcaption class="px-3 py-2 text-xs text-slate-600">{{ __('admin.bookings.delivery.kind.'.$file['kind']) }} · {{ $file['size_kb'] }} KB · {{ $file['time'] }}</figcaption>
                    </figure>
                @endforeach
            </div>
        @endif
    </section>
</div>

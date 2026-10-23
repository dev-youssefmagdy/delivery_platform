@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn ($at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
    $loc = $evidence['location'] ?? [];
    $actor = $loc['actor'] ?? null;
@endphp
<div class="space-y-8">
    <x-admin.alert type="info">{{ __('safety.admin.evidence_notice') }}</x-admin.alert>

    <section>
        <h3 class="mb-2 text-sm font-semibold text-slate-800">{{ __('safety.admin.evidence.narrative') }}</h3>
        <p class="whitespace-pre-line rounded-(--radius-control) border border-slate-200 bg-slate-50 p-3 text-sm text-slate-800">{{ $evidence['narrative'] ?? __('safety.admin.evidence.no_narrative') }}</p>
        <x-admin.detail-list class="mt-3" :cols="2">
            <x-admin.detail-list.item :label="__('safety.admin.evidence.callback')" :value="$evidence['callback_phone']" />
        </x-admin.detail-list>
    </section>

    <section>
        <h3 class="mb-2 text-sm font-semibold text-slate-800">{{ __('safety.admin.evidence.location') }}</h3>
        @if ($actor)
            <x-admin.map-preview :lat="$actor['lat']" :lng="$actor['lng']" :label="__('safety.admin.evidence.location')" />
            <p class="mt-2 text-xs text-slate-500">
                {{ __('safety.admin.evidence.fix', ['source' => __('safety.admin.evidence.source.'.($actor['source'] ?? 'device')), 'time' => $when(\Carbon\CarbonImmutable::parse($actor['captured_at'])) ?? '—', 'accuracy' => isset($actor['accuracy_m']) ? round($actor['accuracy_m']).' m' : '—']) }}
            </p>
        @else
            <x-admin.empty-state icon="map" :title="__('safety.admin.evidence.no_location')" compact />
        @endif
        @if (! empty($loc['counterpart']))
            <p class="mt-3 text-sm text-slate-700">{{ __('safety.admin.evidence.counterpart') }}: {{ $loc['counterpart']['lat'] }}, {{ $loc['counterpart']['lng'] }} · {{ $when(\Carbon\CarbonImmutable::parse($loc['counterpart']['captured_at'])) }}</p>
        @endif
    </section>

    <section>
        <h3 class="mb-2 text-sm font-semibold text-slate-800">{{ __('safety.admin.evidence.files') }}</h3>
        @if ($evidence['attachments'] === [])
            <x-admin.empty-state icon="photo" :title="__('safety.admin.evidence.no_files')" compact />
        @else
            <ul class="divide-y divide-slate-100 rounded-(--radius-control) border border-slate-200">
                @foreach ($evidence['attachments'] as $file)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-sm">
                        <span class="font-medium text-slate-900">{{ $file['mime'] }} <span class="text-xs font-normal text-slate-500">· {{ number_format($file['size_bytes'] / 1024, 0) }} KB · {{ $file['scan'] }} · {{ $when($file['at']) }}</span></span>
                        <a class="text-brand-700 hover:underline" href="{{ $file['url'] }}" target="_blank" rel="noopener">{{ __('safety.admin.evidence.open_file') }}</a>
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-slate-500">{{ __('safety.admin.evidence.files_hint', ['minutes' => (int) config('safety.evidence_url_ttl_minutes', 5)]) }}</p>
        @endif
    </section>

    <section>
        <h3 class="mb-2 text-sm font-semibold text-slate-800">{{ __('safety.admin.evidence.contacts') }}</h3>
        @if ($evidence['contact_actions']->isEmpty())
            <x-admin.empty-state icon="users" :title="__('safety.admin.evidence.no_contacts')" compact />
        @else
            <ul class="divide-y divide-slate-100 rounded-(--radius-control) border border-slate-200">
                @foreach ($evidence['contact_actions'] as $action)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-sm">
                        <span class="font-medium text-slate-900">{{ $action->contact_name ?? '—' }} <span class="text-xs font-normal text-slate-500">{{ $action->contact_phone_masked }} · {{ $action->action->label() }}@if ($action->included_location) · {{ __('safety.admin.evidence.with_location') }}@endif @if ($action->reason) · {{ $action->reason }}@endif</span></span>
                        <span class="flex items-center gap-2 text-slate-500"><x-admin.badge :value="$action->status" /> {{ $when($action->created_at) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section>
        <h3 class="mb-2 text-sm font-semibold text-slate-800">{{ __('safety.admin.evidence.thread') }}</h3>
        <ul class="space-y-3">
            @forelse ($evidence['events']->filter(fn ($event) => $event->body !== null) as $event)
                <li class="rounded-(--radius-control) border border-slate-200 p-3 text-sm">
                    <p class="mb-1 text-xs text-slate-500">{{ $event->type->label() }} · {{ $event->actor_type->label() }} · {{ $when($event->created_at) }}@if (! $event->is_public) · {{ __('safety.admin.evidence.internal') }}@endif</p>
                    <p class="whitespace-pre-line text-slate-800">{{ $event->body }}</p>
                </li>
            @empty
                <x-admin.empty-state icon="inbox" :title="__('safety.admin.evidence.no_thread')" compact />
            @endforelse
        </ul>
    </section>
</div>

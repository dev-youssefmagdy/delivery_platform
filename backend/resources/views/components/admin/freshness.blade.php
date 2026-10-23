@props(['at' => null, 'live' => false, 'staleAfter' => 150])
{{-- Data-freshness indicator: "Live", or "Data as of <time>" turning amber when the aggregation is late (or never ran). --}}
@php
    $stale = ! $live && ($at === null || $at->diffInMinutes(now(), true) > $staleAfter);
    $time = $at?->copy()->setTimezone(config('admin.display_timezone', 'UTC'))->locale(app()->getLocale())->translatedFormat('Y-m-d H:i');
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs', 'border-emerald-200 bg-success-50 text-success-700' => $live, 'border-amber-200 bg-warning-50 text-warning-700' => $stale, 'border-slate-200 bg-slate-50 text-slate-700' => ! $live && ! $stale]) }} data-freshness>
    <x-admin.icon :name="$stale ? 'exclamation' : 'clock'" class="size-4" />
    <span>
        @if ($live)
            {{ __('reporting.freshness_note.live') }}
        @elseif ($at === null)
            {{ __('reporting.freshness_note.never') }}
        @elseif ($stale)
            {{ __('reporting.freshness_note.stale', ['time' => $time]) }}
        @else
            {{ __('reporting.freshness_note.as_of', ['time' => $time]) }}
        @endif
    </span>
</span>

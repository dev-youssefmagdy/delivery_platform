@props(['label', 'value', 'icon' => null, 'tone' => 'primary', 'hint' => null, 'href' => null])
@php
    $tones = [
        'primary' => 'bg-brand-50 text-brand-700',
        'success' => 'bg-success-50 text-success-700',
        'warning' => 'bg-warning-50 text-warning-700',
        'danger' => 'bg-danger-50 text-danger-700',
        'info' => 'bg-info-50 text-info-700',
        'neutral' => 'bg-neutral-100 text-neutral-700',
    ];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['card flex items-center gap-4 p-5', 'transition hover:border-brand-300' => $href]) }}>
    @if ($icon)
        <span @class(['inline-flex size-11 shrink-0 items-center justify-center rounded-xl', $tones[$tone] ?? $tones['primary']])>
            <x-admin.icon :name="$icon" class="size-6" />
        </span>
    @endif
    <div class="min-w-0">
        <p class="truncate text-sm text-slate-500">{{ $label }}</p>
        <p class="mt-0.5 text-2xl font-semibold text-slate-900 tabular-nums">{{ $value }}</p>
        @if ($hint)
            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $hint }}</p>
        @endif
    </div>
</{{ $tag }}>

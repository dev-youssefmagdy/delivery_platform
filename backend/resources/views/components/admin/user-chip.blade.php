@php($tag = $href ? 'a' : 'span')
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['inline-flex min-w-0 items-center gap-2.5']) }}>
    <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-800" aria-hidden="true">{{ $initials }}</span>
    <span class="min-w-0 leading-tight">
        <span class="block truncate text-sm font-medium text-slate-800">{{ $name }}</span>
        @if ($subtitle)
            <span class="ltr-only block truncate text-xs text-slate-500">{{ $subtitle }}</span>
        @endif
    </span>
</{{ $tag }}>

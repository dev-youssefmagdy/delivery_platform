@props(['label', 'value' => null, 'full' => false])
<div {{ $attributes->class(['min-w-0', 'sm:col-span-full' => $full]) }}>
    <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">{{ $label }}</dt>
    <dd class="mt-1 text-sm break-words text-slate-900">
        @if (trim((string) $slot) !== '')
            {{ $slot }}
        @elseif ($value !== null && $value !== '')
            {{ $value }}
        @else
            <span class="text-slate-400">{{ __('admin.common.not_set') }}</span>
        @endif
    </dd>
</div>

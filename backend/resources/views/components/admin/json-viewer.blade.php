<div {{ $attributes->class(['overflow-hidden rounded-(--radius-control) border border-slate-200 bg-slate-950']) }} x-data="{ open: @js(! $collapsed) }">
    @if ($collapsed)
        <button type="button" class="flex w-full items-center justify-between px-4 py-2 text-xs font-medium text-slate-300" x-on:click="open = !open" x-bind:aria-expanded="open">
            <span>JSON</span>
            <x-admin.icon name="chevron-down" class="size-4" x-bind:class="open ? 'rotate-180' : ''" />
        </button>
    @endif
    <pre x-show="open" dir="ltr" class="max-h-96 overflow-auto p-4 text-start font-mono text-xs leading-relaxed text-emerald-200">{{ $json }}</pre>
</div>

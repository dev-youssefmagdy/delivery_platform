@props(['icon' => 'inbox', 'title' => null, 'description' => null, 'compact' => false])
<div {{ $attributes->class(['flex flex-col items-center justify-center text-center', 'px-6 py-12' => ! $compact, 'px-4 py-6' => $compact]) }}>
    <span @class(['inline-flex items-center justify-center rounded-full bg-slate-100 text-slate-400', 'size-12' => ! $compact, 'size-9' => $compact])>
        <x-admin.icon :name="$icon" @class(['size-6' => ! $compact, 'size-5' => $compact]) />
    </span>
    @if ($title)
        <p @class(['font-semibold text-slate-800', 'mt-4 text-base' => ! $compact, 'mt-2 text-sm' => $compact])>{{ $title }}</p>
    @endif
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @endif
    @if (trim((string) $slot) !== '')
        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">{{ $slot }}</div>
    @endif
</div>

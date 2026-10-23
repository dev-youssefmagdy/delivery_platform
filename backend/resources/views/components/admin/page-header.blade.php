@props(['title', 'subtitle' => null, 'breadcrumbs' => []])
{{-- breadcrumbs: list of ['label' => string, 'url' => ?string] (the last item is the current page) --}}
<div {{ $attributes->class(['mb-6 flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        @if ($breadcrumbs)
            <nav aria-label="{{ __('admin.layout.breadcrumbs') }}" class="mb-2">
                <ol class="flex flex-wrap items-center gap-1.5 text-sm text-slate-500">
                    @foreach ($breadcrumbs as $crumb)
                        <li class="flex items-center gap-1.5">
                            @if (! $loop->first)
                                <x-admin.icon name="chevron-right" class="size-3.5 text-slate-500" />
                            @endif
                            @if (! empty($crumb['url']) && ! $loop->last)
                                <a href="{{ $crumb['url'] }}" class="hover:text-slate-800">{{ $crumb['label'] }}</a>
                            @else
                                <span @if ($loop->last) aria-current="page" class="text-slate-700" @endif>{{ $crumb['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
        <h1 class="truncate text-2xl font-semibold text-slate-900">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>

    @if (trim((string) $slot) !== '')
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>

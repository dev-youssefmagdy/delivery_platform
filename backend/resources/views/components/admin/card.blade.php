@props(['title' => null, 'subtitle' => null, 'padding' => true])
<section {{ $attributes->class(['card']) }}>
    @if ($title || isset($actions))
        <header class="card-header">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['card-body' => $padding])>{{ $slot }}</div>

    @isset($footer)
        <footer class="card-footer">{{ $footer }}</footer>
    @endisset
</section>

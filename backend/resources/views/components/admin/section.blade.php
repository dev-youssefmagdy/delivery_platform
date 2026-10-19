@props(['title' => null, 'description' => null])
<section {{ $attributes->class(['grid gap-6 border-b border-slate-100 py-6 first:pt-0 last:border-b-0 last:pb-0 lg:grid-cols-3']) }}>
    @if ($title || $description)
        <div>
            @if ($title)
                <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
            @endif
            @if ($description)
                <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
            @endif
        </div>
    @endif
    <div @class(['space-y-5', 'lg:col-span-2' => $title || $description, 'lg:col-span-3' => ! $title && ! $description])>{{ $slot }}</div>
</section>

@props(['title' => null])
{{-- Narrow, private page (trip share): the public layout in minimal mode, never indexed (PageMeta::private). --}}
<x-website.layouts.site :meta="app(\App\Support\Website\MetaBuilder::class)->private($title ?? (string) config('website.brand.name'))" :minimal="true">
    <div class="mx-auto max-w-lg px-4 py-6">
        {{ $slot }}
    </div>
</x-website.layouts.site>

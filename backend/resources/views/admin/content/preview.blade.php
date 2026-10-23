{{-- Draft preview: the same payload the public API / website would receive, built from the unpublished row. --}}
@php($prefix = $type->routePrefix())
<x-admin.layouts.app :title="__('content.preview.title')">
    <x-admin.page-header
        :title="__('content.preview.title')"
        :subtitle="__('content.preview.subtitle')"
        :breadcrumbs="[['label' => $type->label(), 'url' => route($prefix.'.index')], ['label' => __('content.preview.title')]]"
    >
        @foreach (\App\Enums\Common\Locale::cases() as $other)
            <x-admin.button :variant="$other === $locale ? 'primary' : 'secondary'" :href="route('admin.content.preview', ['contentType' => $type->value, 'content' => $content->getKey(), 'locale' => $other->value])">{{ $other->label() }}</x-admin.button>
        @endforeach
        <x-admin.button variant="ghost" icon="arrow-left" :href="route($prefix.'.show', $content)">{{ __('admin.common.back') }}</x-admin.button>
    </x-admin.page-header>

    @if ($payload === [])
        <x-admin.alert type="warning">{{ __('content.preview.unavailable', ['locale' => $locale->label()]) }}</x-admin.alert>
    @else
        @if (($payload['locale'] ?? $locale->value) !== $locale->value)
            <x-admin.alert type="info" class="mb-4">{{ __('content.preview.fallback_notice') }}</x-admin.alert>
        @endif
        <x-admin.card lang="{{ $locale->value }}" dir="{{ $locale->direction() }}">
            @isset($payload['hero_image']['url'])
                <img src="{{ $payload['hero_image']['url'] }}" alt="{{ $payload['hero_image']['alt'] }}" class="mb-6 max-h-72 w-full rounded-(--radius-control) object-cover">
            @endisset
            @isset($payload['cover_image']['url'])
                <img src="{{ $payload['cover_image']['url'] }}" alt="{{ $payload['cover_image']['alt'] }}" class="mb-6 max-h-72 w-full rounded-(--radius-control) object-cover">
            @endisset
            @isset($payload['image']['url'])
                <img src="{{ $payload['image']['url'] }}" alt="{{ $payload['image']['alt'] }}" class="mb-6 max-h-72 w-full rounded-(--radius-control) object-cover">
            @endisset

            <h2 class="text-2xl font-semibold text-slate-900">{{ $payload['title'] ?? $payload['question'] ?? '' }}</h2>
            @if (! empty($payload['summary']))
                <p class="mt-2 text-slate-600">{{ $payload['summary'] }}</p>
            @endif
            @if (! empty($payload['body']))
                <p class="mt-2 text-slate-600">{{ $payload['body'] }}</p>
            @endif
            @if (! empty($payload['cta']))
                <p class="mt-3"><span class="btn btn-primary btn-sm">{{ $payload['cta']['label'] }}</span> <span class="text-xs text-slate-500" dir="ltr">{{ $payload['cta']['url'] }}</span></p>
            @endif

            @if (! empty($payload['body_html']))
                <x-admin.content.rendered :html="$payload['body_html']" class="mt-6" />
            @endif
            @if (! empty($payload['answer_html']))
                <x-admin.content.rendered :html="$payload['answer_html']" class="mt-6" />
            @endif

            @foreach ($payload['blocks'] ?? [] as $block)
                <section class="mt-8 rounded-(--radius-control) border border-slate-200 p-5">
                    <p class="mb-1 text-xs font-medium tracking-wide text-slate-500 uppercase">{{ \App\Enums\Content\BlockType::from($block['type'])->label() }}</p>
                    <h3 class="text-lg font-semibold text-slate-900">{{ $block['title'] }}</h3>
                    @if ($block['body_html'])
                        <x-admin.content.rendered :html="$block['body_html']" class="mt-2" />
                    @endif
                    @if ($block['items'] !== [])
                        <ul class="mt-3 grid gap-3 sm:grid-cols-2">
                            @foreach ($block['items'] as $item)
                                <li class="rounded-(--radius-control) bg-slate-50 p-3"><p class="font-medium text-slate-900">{{ $item['title'] }}</p>@if ($item['text'])<p class="text-sm text-slate-600">{{ $item['text'] }}</p>@endif</li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($block['cta'])
                        <p class="mt-3"><span class="btn btn-primary btn-sm">{{ $block['cta']['label'] }}</span> <span class="text-xs text-slate-500" dir="ltr">{{ $block['cta']['url'] }}</span></p>
                    @endif
                </section>
            @endforeach

            @foreach ($payload['items'] ?? [] as $item)
                <p class="mt-2"><span class="font-medium">{{ $item['label'] }}</span> <span class="text-xs text-slate-500" dir="ltr">{{ $item['url'] }}</span></p>
            @endforeach

            @isset($payload['seo'])
                <div class="mt-8 rounded-(--radius-control) border border-dashed border-slate-300 p-4 text-sm">
                    <p class="mb-2 font-medium text-slate-700">{{ __('content.preview.seo') }}</p>
                    <p class="text-brand-700">{{ $payload['seo']['title'] }}</p>
                    <p class="text-slate-600">{{ $payload['seo']['description'] }}</p>
                    @if ($payload['seo']['noindex'])
                        <p class="mt-1 text-amber-700">noindex</p>
                    @endif
                </div>
            @endisset
        </x-admin.card>
    @endif
</x-admin.layouts.app>

@props(['image', 'sizes' => '(min-width: 1024px) 1024px, 100vw', 'eager' => false, 'class' => ''])
{{--
    A CMS image (ContentReadService payload): srcset from the generated variants, intrinsic width/height so the box is reserved
    before the file arrives (no layout shift), lazy loading below the fold. Empty alt = decorative.
--}}
@php
    $variants = collect((array) ($image['variants'] ?? []))->sortKeys();
    $srcset = $variants->map(fn ($url, $width) => $url.' '.$width.'w')->implode(', ');
    $width = $image['width'] ?? null;
    $height = $image['height'] ?? null;
@endphp
@if (! empty($image['url']))
    <img
        src="{{ $image['url'] }}"
        @if ($srcset !== '') srcset="{{ $srcset }}, {{ $image['url'] }} {{ $width }}w" sizes="{{ $sizes }}" @endif
        alt="{{ $image['alt'] ?? '' }}"
        @if ($width && $height) width="{{ $width }}" height="{{ $height }}" @endif
        @if ($eager) fetchpriority="high" @else loading="lazy" @endif
        decoding="async"
        class="h-auto w-full {{ $class }}"
    >
@endif

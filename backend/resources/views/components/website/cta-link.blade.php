@props(['url', 'variant' => 'primary'])
@php
    $target = $sitePaths->resolve($url);
    $classes = $variant === 'primary'
        ? 'bg-brand-700 text-white hover:bg-brand-800'
        : 'border border-white/70 text-white hover:bg-white/10';
@endphp
<a href="{{ $target['href'] }}" @if ($target['external']) target="_blank" rel="noopener noreferrer" @endif {{ $attributes->class(['inline-flex items-center justify-center rounded-lg px-6 py-3 text-base font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700', $classes]) }}>{{ $slot }}</a>

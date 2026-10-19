@php($sized = str_contains((string) $attributes->get('class'), 'size-'))
<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.5"
    {{ $attributes->class(['shrink-0', 'size-5' => ! $sized, 'rtl:-scale-x-100' => $directional()]) }}
    @if ($decorative) aria-hidden="true" focusable="false" @endif
>
    @foreach ($paths() as $path)
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
    @endforeach
</svg>

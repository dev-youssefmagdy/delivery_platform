@props(['lines' => 3, 'variant' => 'text'])
<div {{ $attributes->class(['space-y-3']) }} aria-hidden="true">
    @if ($variant === 'card')
        <div class="skeleton h-32 w-full"></div>
    @endif
    @for ($i = 0; $i < (int) $lines; $i++)
        <div class="skeleton h-4" style="width: {{ [100, 92, 78, 85, 64][$i % 5] }}%"></div>
    @endfor
</div>
<span class="sr-only" role="status">{{ __('admin.common.loading') }}</span>

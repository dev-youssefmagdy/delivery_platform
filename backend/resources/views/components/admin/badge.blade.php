<span {{ $attributes->class(['badge', 'badge-'.$tone]) }}>
    @if ($dot)
        <span class="badge-dot" aria-hidden="true"></span>
    @endif
    {{ $text }}
</span>

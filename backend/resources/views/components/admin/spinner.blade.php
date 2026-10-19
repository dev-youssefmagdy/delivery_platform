@props(['size' => 'size-4', 'label' => null])
<svg {{ $attributes->class(['animate-spin', $size]) }} viewBox="0 0 24 24" fill="none" @if ($label) role="status" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
</svg>

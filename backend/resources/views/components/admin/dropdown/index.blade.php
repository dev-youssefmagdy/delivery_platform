@props(['align' => 'end', 'label' => null, 'width' => 'w-56'])
{{-- Trigger goes in <x-slot:trigger>; items are <x-admin.dropdown.item> (or any markup). --}}
<div {{ $attributes->class(['relative']) }} x-data="adminDropdown()" x-on:keydown.escape.stop="close(); $refs.button.focus()" x-on:click.outside="close()">
    <button type="button" x-ref="button" x-on:click="toggle()" x-bind:aria-expanded="open" aria-haspopup="menu" @if ($label) aria-label="{{ $label }}" @endif class="inline-flex">
        {{ $trigger }}
    </button>
    <div
        x-show="open"
        x-cloak
        x-transition.origin.top
        role="menu"
        @class(['absolute z-40 mt-2 overflow-hidden rounded-(--radius-card) border border-slate-200 bg-white py-1 shadow-(--shadow-overlay)', $width, 'end-0' => $align === 'end', 'start-0' => $align !== 'end'])
        x-on:click="if ($event.target.closest('a,[data-dropdown-close]')) close()"
    >
        {{ $slot }}
    </div>
</div>

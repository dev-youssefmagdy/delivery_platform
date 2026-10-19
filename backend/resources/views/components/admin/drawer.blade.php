@props(['id', 'title' => null, 'url' => null])
{{-- Side panel (end side in both directions) for quick views. Same API as <x-admin.modal>. --}}
<div
    {{ $attributes }}
    id="{{ $id }}"
    data-modal="{{ $id }}"
    x-data="adminModal(@js($id), @js($url))"
    x-on:keydown.escape.window="open && hide()"
>
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" data-modal="{{ $id }}">
            <div class="overlay-backdrop" x-show="open" x-transition.opacity x-on:click="hide()"></div>
            <div
                class="drawer-panel"
                x-show="open"
                x-trap.inert.noscroll="open"
                x-transition:enter="transition duration-200 ease-out"
                x-transition:enter-start="translate-x-full rtl:-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition duration-150 ease-in"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full rtl:-translate-x-full"
            >
                <div class="card-header">
                    <h2 id="{{ $id }}-title" class="text-base font-semibold text-slate-900">{{ $title }}</h2>
                    <x-admin.icon-button icon="x-mark" :label="__('admin.common.close')" x-on:click="hide()" />
                </div>
                <div class="flex-1 overflow-y-auto p-5">
                    <div x-show="loading"><x-admin.skeleton :lines="6" /></div>
                    <div x-ref="body" x-show="!loading">{{ $slot }}</div>
                </div>
            </div>
        </div>
    </template>
</div>

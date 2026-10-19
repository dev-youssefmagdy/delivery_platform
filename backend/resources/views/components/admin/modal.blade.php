@props(['id', 'title' => null, 'size' => 'md', 'url' => null])
{{--
    Alpine modal with focus trap + ESC. Open: [data-modal-open="{{ id }}"] (optionally data-modal-url to load HTML),
    or window.dispatchEvent(new CustomEvent('admin-modal:open', { detail: { id, url } })).
    An <x-admin.form> inside closes it on `close_modal` and reloads the referenced table.
--}}
@php
    $width = ['sm' => 'max-w-md', 'md' => 'max-w-lg', 'lg' => 'max-w-2xl', 'xl' => 'max-w-4xl'][$size] ?? 'max-w-lg';
@endphp
<div
    {{ $attributes }}
    id="{{ $id }}"
    data-modal="{{ $id }}"
    x-data="adminModal(@js($id), @js($url))"
    x-on:keydown.escape.window="open && hide()"
>
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" data-modal="{{ $id }}">
            <div class="overlay-backdrop" x-show="open" x-transition.opacity x-on:click="hide()"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="modal-panel {{ $width }}" x-show="open" x-transition x-trap.inert.noscroll="open">
                    <div class="card-header">
                        <h2 id="{{ $id }}-title" class="text-base font-semibold text-slate-900">{{ $title }}</h2>
                        <x-admin.icon-button icon="x-mark" :label="__('admin.common.close')" x-on:click="hide()" />
                    </div>
                    <div class="max-h-[75vh] overflow-y-auto p-5">
                        <div x-show="loading"><x-admin.skeleton :lines="4" /></div>
                        <div x-ref="body" x-show="!loading">{{ $slot }}</div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

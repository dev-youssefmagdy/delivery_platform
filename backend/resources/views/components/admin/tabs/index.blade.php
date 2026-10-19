@props(['tabs' => [], 'active' => null, 'label' => null])
{{--
    tabs: [id => label] or [id => ['label' => string, 'url' => ?string (lazy-loaded panel), 'icon' => ?string]]
    Panels: <x-admin.tabs.panel id="…">…</x-admin.tabs.panel> (panels with a url start empty and load via AJAX).
--}}
@php
    $normalized = collect($tabs)->map(fn ($tab) => is_array($tab) ? $tab : ['label' => $tab]);
    $active ??= $normalized->keys()->first();
@endphp
<div {{ $attributes }} data-tabs x-data="adminTabs(@js($active))">
    <div class="tab-list" role="tablist" @if ($label) aria-label="{{ $label }}" @endif x-on:keydown="keydown($event)">
        @foreach ($normalized as $id => $tab)
            <button
                type="button"
                role="tab"
                class="tab"
                id="tab-{{ $id }}"
                data-tab-trigger="{{ $id }}"
                aria-controls="panel-{{ $id }}"
                x-bind:aria-selected="isActive(@js($id)) ? 'true' : 'false'"
                x-bind:tabindex="isActive(@js($id)) ? 0 : -1"
                x-on:click="select(@js($id))"
            >
                @if (! empty($tab['icon']))
                    <x-admin.icon :name="$tab['icon']" class="size-4" />
                @endif
                <span>{{ $tab['label'] }}</span>
                <span class="tab-error-dot" aria-hidden="true"></span>
            </button>
        @endforeach
    </div>

    <div class="pt-5">{{ $slot }}</div>
</div>

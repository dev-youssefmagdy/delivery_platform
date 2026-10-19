@props(['for', 'title' => null])
{{--
    Filters for the listing whose table id is `for` (Plan §5.4). ALWAYS collapsed on load (even when filters
    are active — the badge shows how many apply). Apply/Enter reloads the table and syncs ?filters[...] to the
    URL; Reset clears inputs, URL and table. Inputs are pre-filled from the query string server-side.
--}}
<section {{ $attributes->class(['card mb-6']) }} x-data="filtersCard(@js($for))">
    <h2>
        <button
            type="button"
            class="flex w-full items-center justify-between gap-3 px-5 py-3.5 text-start"
            x-on:click="open = !open"
            x-bind:aria-expanded="open ? 'true' : 'false'"
            aria-controls="{{ $for }}-filters"
            aria-expanded="false"
        >
            <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <x-admin.icon name="funnel" class="size-4 text-slate-500" />
                {{ $title ?? __('admin.filters.title') }}
                <span class="badge badge-primary" x-show="count > 0" x-cloak data-filters-count>
                    <span x-text="count"></span>
                </span>
                <span class="sr-only" x-text="count > 0 ? @js(__('admin.filters.active', ['count' => '__N__'])).replace('__N__', count) : ''"></span>
            </span>
            <x-admin.icon name="chevron-down" class="size-4 text-slate-500 transition" x-bind:class="open ? 'rotate-180' : ''" />
        </button>
    </h2>

    <div id="{{ $for }}-filters" x-show="open" x-cloak class="border-t border-slate-100">
        <form data-filters-for="{{ $for }}" x-on:submit.prevent="apply()" class="p-5" role="search" autocomplete="off">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {{ $slot }}
            </div>
            <div class="mt-5 flex items-center justify-end gap-2">
                <x-admin.button variant="secondary" icon="refresh" x-on:click="reset()">{{ __('admin.common.reset') }}</x-admin.button>
                <x-admin.button type="submit" icon="funnel">{{ __('admin.common.apply') }}</x-admin.button>
            </div>
        </form>
    </div>
</section>

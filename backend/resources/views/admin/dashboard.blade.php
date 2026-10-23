<x-admin.layouts.app :title="__('reporting.dashboard.title')">
    <x-admin.page-header :title="__('admin.dashboard.welcome', ['name' => $overview->name])" :subtitle="__('reporting.dashboard.subtitle')" />

    <x-admin.card class="mb-6" :title="__('reporting.dashboard.filters_title')">
        <form
            x-data="dashboardFilters(@js(route('admin.dashboard')))"
            x-on:submit.prevent="apply()"
            data-today="{{ now(config('admin.display_timezone', 'UTC'))->toDateString() }}"
            autocomplete="off"
            role="search"
        >
            <div class="mb-4 flex flex-wrap gap-2">
                <x-admin.button type="button" variant="secondary" x-on:click="preset(1)">{{ __('reporting.filters.today') }}</x-admin.button>
                <x-admin.button type="button" variant="secondary" x-on:click="preset(7)">{{ __('reporting.filters.last_7') }}</x-admin.button>
                <x-admin.button type="button" variant="secondary" x-on:click="preset(30)">{{ __('reporting.filters.last_30') }}</x-admin.button>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-admin.report.range-fields :timezones="$timezones" :currencies="$board->currencies" />
            </div>
            <p class="form-error mt-2" x-show="errorFor('filters.range.to') || errorFor('filters.range.from') || errorFor('filters.timezone')" x-cloak x-text="errorFor('filters.range.to') || errorFor('filters.range.from') || errorFor('filters.timezone')" role="alert"></p>
            <div class="mt-4 flex justify-end">
                <x-admin.button type="submit" icon="funnel" x-bind:disabled="loading">{{ __('reporting.filters.apply') }}</x-admin.button>
            </div>
        </form>
    </x-admin.card>

    <div id="dashboard-widgets" aria-live="polite">
        @include('admin.dashboard._widgets')
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <x-admin.card :title="__('admin.dashboard.quick_links')">
            @php($linkGroups = collect($overview->menu)->reject(fn ($group) => $group['key'] === 'dashboard'))
            @forelse ($linkGroups as $group)
                <div class="mb-4 last:mb-0">
                    <h3 class="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ $group['label'] }}</h3>
                    <ul class="grid gap-2 sm:grid-cols-2">
                        @foreach ($group['items'] as $item)
                            <li>
                                <a href="{{ $item['url'] }}" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:border-brand-300 hover:bg-brand-50">
                                    <x-admin.icon :name="$item['icon']" class="size-4 text-slate-500" />
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <x-admin.empty-state icon="inbox" :title="__('admin.dashboard.no_links')" compact />
            @endforelse
        </x-admin.card>

        <div class="grid content-start gap-4 sm:grid-cols-3 xl:grid-cols-1">
            <x-admin.stat-tile :label="__('admin.dashboard.your_roles')" :value="count($overview->roleLabels)" icon="key" :hint="implode(' · ', $overview->roleLabels)" />
            <x-admin.stat-tile :label="__('admin.dashboard.your_permissions')" :value="$overview->isSuperAdmin ? __('admin.dashboard.all') : $overview->permissionCount" icon="shield" tone="info" />
            <x-admin.stat-tile
                :label="__('admin.dashboard.mfa')"
                :value="$overview->mfaEnabled ? __('admin.security.mfa_on') : __('admin.security.mfa_off')"
                icon="lock-closed"
                :tone="$overview->mfaEnabled ? 'success' : 'warning'"
                :href="route('admin.security.index')"
            />
        </div>
    </div>
</x-admin.layouts.app>

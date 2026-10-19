<x-admin.layouts.app :title="__('admin.dashboard.title')">
    <x-admin.page-header :title="__('admin.dashboard.welcome', ['name' => $overview->name])" :subtitle="__('admin.dashboard.subtitle')" />

    <div class="grid gap-4 sm:grid-cols-3">
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

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
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

        <x-admin.card :title="__('admin.dashboard.coming_title')" :subtitle="__('admin.dashboard.coming_subtitle')">
            @forelse ($overview->pendingSections as $section)
                <div class="mb-3 rounded-lg border border-dashed border-slate-300 px-4 py-3 last:mb-0">
                    <p class="text-sm font-medium text-slate-800">{{ $section['title'] }}</p>
                    <p class="text-sm text-slate-500">{{ $section['availableAfter'] }}</p>
                </div>
            @empty
                <x-admin.empty-state icon="inbox" :title="__('admin.dashboard.no_sections')" compact />
            @endforelse
        </x-admin.card>
    </div>
</x-admin.layouts.app>

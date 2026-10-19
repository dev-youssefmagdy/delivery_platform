{{-- Permission-aware navigation built from config/admin_menu.php (App\Support\Admin\AdminMenu). --}}
@php($groups = app(\App\Support\Admin\AdminMenu::class)->visibleGroups())

<div x-show="mobileOpen" x-cloak x-transition.opacity class="overlay-backdrop lg:hidden" x-on:click="mobileOpen = false"></div>

<aside
    class="fixed inset-y-0 start-0 z-50 flex w-(--spacing-sidebar) flex-col bg-brand-950 text-slate-200 transition-all duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
    x-bind:class="{
        'translate-x-0': mobileOpen,
        '-translate-x-full rtl:translate-x-full': !mobileOpen,
        'lg:w-(--spacing-sidebar-collapsed)': collapsed,
    }"
    x-on:keydown.escape="mobileOpen = false"
    aria-label="{{ __('admin.layout.main_navigation') }}"
>
    <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-white/10 px-4">
        <a href="{{ route('admin.home') }}" class="flex min-w-0 items-center gap-2 font-semibold text-white">
            <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-600">
                <x-admin.icon name="bolt" class="size-5" />
            </span>
            <span class="truncate" x-show="!collapsed">{{ __('admin.app_name') }}</span>
        </a>
        <button type="button" class="icon-btn text-slate-300 hover:bg-white/10 hover:text-white lg:hidden" x-on:click="mobileOpen = false" aria-label="{{ __('admin.layout.close_menu') }}">
            <x-admin.icon name="x-mark" class="size-5" />
        </button>
    </div>

    <nav class="flex-1 space-y-4 overflow-y-auto px-3 py-4">
        @foreach ($groups as $group)
            <div x-data="{ open: true }">
                <button type="button" class="flex w-full items-center justify-between px-3 pb-1 text-xs font-semibold tracking-wider text-slate-400 uppercase" x-on:click="open = !open" x-bind:aria-expanded="open" x-show="!collapsed">
                    <span>{{ $group['label'] }}</span>
                    <x-admin.icon name="chevron-down" class="size-3.5 transition" x-bind:class="open ? '' : '-rotate-90 rtl:rotate-90'" />
                </button>
                <ul class="space-y-0.5" x-show="open || collapsed">
                    @foreach ($group['items'] as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="nav-link" @if ($item['active']) aria-current="page" @endif title="{{ $item['label'] }}">
                                <x-admin.icon :name="$item['icon']" class="size-5 shrink-0" />
                                <span class="truncate" x-show="!collapsed">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="hidden border-t border-white/10 p-3 lg:block">
        <button type="button" class="nav-link w-full" x-on:click="toggleCollapsed()" x-bind:aria-pressed="collapsed" aria-label="{{ __('admin.layout.toggle_sidebar') }}">
            <x-admin.icon name="chevron-left" class="size-5 shrink-0 transition" x-bind:class="collapsed ? 'rotate-180' : ''" />
            <span x-show="!collapsed">{{ __('admin.layout.toggle_sidebar') }}</span>
        </button>
    </div>
</aside>

{{-- Topbar: mobile menu, locale switcher (persisted on the admin), notification bell, account menu. --}}
@php
    $user = auth()->user();
    $currentLocale = app()->getLocale();
@endphp
<header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
    <button type="button" class="icon-btn lg:hidden" x-on:click="mobileOpen = true" aria-label="{{ __('admin.layout.open_menu') }}">
        <x-admin.icon name="bars" class="size-5" />
    </button>

    <div class="flex-1"></div>

    <x-admin.dropdown align="end" :label="__('admin.layout.language')">
        <x-slot:trigger>
            <span class="btn btn-ghost btn-sm">
                <x-admin.icon name="language" class="size-4" />
                <span>{{ \App\Enums\Common\Locale::from($currentLocale)->label() }}</span>
            </span>
        </x-slot:trigger>

        @foreach (\App\Enums\Common\Locale::cases() as $locale)
            <x-admin.form :action="route('admin.locale.update')" :live-validation="false" class="block">
                <input type="hidden" name="locale" value="{{ $locale->value }}">
                <button type="submit" role="menuitem" lang="{{ $locale->value }}" class="flex w-full items-center justify-between gap-3 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50" @if ($locale->value === $currentLocale) aria-current="true" @endif>
                    <span>{{ $locale->label() }}</span>
                    @if ($locale->value === $currentLocale)
                        <x-admin.icon name="check" class="size-4 text-brand-600" />
                    @endif
                </button>
            </x-admin.form>
        @endforeach
    </x-admin.dropdown>

    @if ($user instanceof \App\Models\Admin)
        <x-admin.notification-bell />
    @endif

    <div class="flex items-center gap-2 border-s border-slate-200 ps-3">
        @if ($user)
            <x-admin.dropdown align="end" :label="__('admin.layout.account_menu')" width="w-56">
                <x-slot:trigger>
                    <span class="flex items-center gap-2 rounded-lg px-1.5 py-1 hover:bg-slate-50">
                        <x-admin.user-chip :name="(string) ($user->name ?? $user->email ?? '')" :subtitle="$user->email ?? null" />
                        <x-admin.icon name="chevron-down" class="size-4 text-slate-500" />
                    </span>
                </x-slot:trigger>

                <x-admin.dropdown.item :href="route('admin.security.index')" icon="user">{{ __('admin.layout.my_account') }}</x-admin.dropdown.item>
                <x-admin.form :action="route('admin.logout')" :live-validation="false" class="block">
                    <button type="submit" role="menuitem" class="flex w-full items-center gap-2.5 px-4 py-2 text-start text-sm text-danger-700 hover:bg-slate-50">
                        <x-admin.icon name="logout" class="size-4" />
                        <span>{{ __('admin.layout.sign_out') }}</span>
                    </button>
                </x-admin.form>
            </x-admin.dropdown>
        @endif
    </div>
</header>

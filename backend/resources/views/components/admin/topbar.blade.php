{{-- Topbar: mobile menu, locale switcher (session; persisted per admin from P1.1), notifications, profile. --}}
@php
    $user = auth()->user();
    $currentLocale = app()->getLocale();
    $hasNotifications = $user !== null && method_exists($user, 'unreadNotifications');
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

    @if ($hasNotifications)
        <x-admin.dropdown align="end" :label="__('admin.layout.notifications')" width="w-80">
            <x-slot:trigger>
                <span class="icon-btn relative">
                    <x-admin.icon name="bell" class="size-5" />
                    @if ($user->unreadNotifications()->exists())
                        <span class="absolute end-1.5 top-1.5 size-2 rounded-full bg-danger-600"></span>
                    @endif
                </span>
            </x-slot:trigger>
            <x-admin.empty-state icon="bell" :title="__('admin.layout.no_notifications')" compact />
        </x-admin.dropdown>
    @endif

    <div class="flex items-center gap-2 border-s border-slate-200 ps-3">
        @if ($user)
            <x-admin.user-chip :name="(string) ($user->name ?? $user->email ?? '')" :subtitle="$user->email ?? null" />
        @else
            <x-admin.user-chip :name="__('admin.layout.guest')" />
        @endif
    </div>
</header>

{{-- Top-bar bell: unread dot, the latest notifications of the signed-in admin, "mark all as read". --}}
<x-admin.dropdown align="end" :label="__('admin.layout.notifications')" width="w-80">
    <x-slot:trigger>
        <span class="icon-btn relative">
            <x-admin.icon name="bell" class="size-5" />
            @if ($unread > 0)
                <span class="absolute end-1.5 top-1.5 size-2 rounded-full bg-danger-600"></span>
                <span class="sr-only">{{ __('admin.layout.unread_notifications', ['count' => $unread]) }}</span>
            @endif
        </span>
    </x-slot:trigger>

    <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-2">
        <p class="text-sm font-semibold text-slate-800">{{ __('admin.layout.notifications') }}</p>
        @if ($unread > 0)
            <x-admin.form :action="route('admin.notifications.read-all')" :live-validation="false" class="block">
                <button type="submit" class="text-xs font-medium text-brand-700 hover:underline">{{ __('admin.layout.mark_all_read') }}</button>
            </x-admin.form>
        @endif
    </div>

    @forelse ($items as $item)
        @php($notification = $item['notification'])
        <x-admin.form :action="route('admin.notifications.read', $notification->uuid)" :live-validation="false" class="block">
            <button type="submit" role="menuitem" @class(['flex w-full items-start gap-3 px-4 py-3 text-start hover:bg-slate-50', 'bg-brand-50/40' => $notification->read_at === null])>
                <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-brand-600' => $notification->read_at === null, 'bg-transparent' => $notification->read_at !== null]) aria-hidden="true"></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-slate-800">{{ $notification->title }}</span>
                    <span class="block text-xs text-slate-600">{{ $notification->body }}</span>
                    <span class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                        <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                        @if ($item['url'])
                            <span class="font-medium text-brand-700">{{ __('admin.layout.notification_download') }}</span>
                        @endif
                    </span>
                </span>
            </button>
        </x-admin.form>
    @empty
        <x-admin.empty-state icon="bell" :title="__('admin.layout.no_notifications')" compact />
    @endforelse
</x-admin.dropdown>

<x-admin.layouts.app :title="__('admin.operations.title')">
    <x-admin.page-header
        :title="__('admin.operations.title')"
        :subtitle="__('admin.operations.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.operations.title')]]"
    >
        <x-admin.realtime-status />
        <x-admin.button variant="secondary" icon="clipboard" :href="route('admin.bookings.index')">{{ __('admin.bookings.title') }}</x-admin.button>
        <x-admin.button variant="secondary" icon="clock" :href="route('admin.bookings.no-offer-queue')">{{ __('admin.bookings.queue.menu') }}</x-admin.button>
    </x-admin.page-header>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-admin.stat-tile stat="providers" :label="__('admin.operations.stats.providers')" :value="count($snapshot['providers'])" icon="users" tone="success" />
        <x-admin.stat-tile stat="busy" :label="__('admin.operations.stats.busy')" :value="collect($snapshot['providers'])->where('busy', true)->count()" icon="bolt" tone="primary" />
        <x-admin.stat-tile stat="bookings" :label="__('admin.operations.stats.bookings')" :value="count($snapshot['bookings'])" icon="clipboard" tone="info" />
        <x-admin.stat-tile stat="searching" :label="__('admin.operations.stats.searching')" :value="collect($snapshot['bookings'])->whereIn('status', \App\Enums\Booking\BookingStatus::searchingValues())->count()" icon="clock" tone="warning" />
    </div>

    <x-admin.card :padding="false">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-slate-100 px-5 py-3 text-sm">
            <label class="inline-flex items-center gap-2"><input type="checkbox" class="size-4 rounded border-slate-300" data-map-layer="providers" checked> <span class="inline-block size-3 rounded-full bg-success-600" aria-hidden="true"></span> {{ __('admin.operations.layers.providers') }}</label>
            <label class="inline-flex items-center gap-2"><input type="checkbox" class="size-4 rounded border-slate-300" data-map-layer="bookings" checked> <span class="inline-block size-3 rounded-full bg-brand-600" aria-hidden="true"></span> {{ __('admin.operations.layers.bookings') }}</label>
            <span class="text-xs text-slate-500">{{ __('admin.operations.privacy') }}</span>
            <span class="ms-auto text-xs text-slate-500">{{ __('admin.operations.updated') }}: <time data-map-updated>—</time></span>
        </div>
        @if ($snapshot['truncated'])
            <x-admin.alert type="warning" class="m-4">{{ __('admin.operations.truncated') }}</x-admin.alert>
        @endif
        <div
            class="h-[28rem] w-full lg:h-[34rem]"
            dir="ltr"
            data-operations-map
            data-snapshot-url="{{ route('admin.operations.map.snapshot') }}"
            data-snapshot='@json($snapshot)'
            data-poll-seconds="30"
            role="application"
            aria-label="{{ __('admin.operations.title') }}"
        ></div>
    </x-admin.card>
</x-admin.layouts.app>

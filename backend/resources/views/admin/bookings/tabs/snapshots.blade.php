<p class="mb-4 text-sm text-slate-500">{{ __('admin.bookings.snapshots.intro') }}</p>
@if ($snapshots === [])
    <x-admin.empty-state icon="document" :title="__('admin.common.no_results')" compact />
@else
    <div class="space-y-5">
        @foreach ($snapshots as $key => $data)
            <section>
                <h3 class="mb-2 text-sm font-semibold text-slate-800">{{ __('admin.bookings.snapshots.'.$key) }}</h3>
                <x-admin.json-viewer :data="$data" collapsed />
            </section>
        @endforeach
    </div>
@endif

<p class="mb-4 text-sm text-slate-500">{{ __('admin.bookings.ratings.intro') }}</p>
@if ($ratings === [])
    <x-admin.empty-state icon="star" :title="__('admin.bookings.ratings.none')" compact />
@else
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @foreach ($ratings as $rating)
            <article class="rounded-(--radius-control) border border-slate-200 p-4">
                <header class="flex items-center justify-between gap-2">
                    <p class="text-sm font-medium text-slate-900">{{ __('admin.bookings.ratings.from', ['party' => $rating['from']]) }}</p>
                    <x-admin.badge :label="$rating['status']" color="neutral" />
                </header>
                <p class="mt-2 text-lg text-warning-600" aria-label="{{ __('admin.ratings.stars_label', ['count' => $rating['stars']]) }}">{{ str_repeat('★', $rating['stars']).str_repeat('☆', 5 - $rating['stars']) }}</p>
                @if ($rating['tags'] !== [])
                    <p class="mt-1 flex flex-wrap gap-1.5">
                        @foreach ($rating['tags'] as $tag)
                            <x-admin.badge :label="$tag" color="info" :dot="false" />
                        @endforeach
                    </p>
                @endif
                @if ($rating['comment'])
                    <p class="mt-2 text-sm text-slate-700">{{ $rating['comment'] }}</p>
                @endif
                <p class="mt-2 text-xs text-slate-500">{{ $rating['time'] }}</p>
            </article>
        @endforeach
    </div>
@endif

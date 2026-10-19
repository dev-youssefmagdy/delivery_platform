@props(['items' => []])
{{--
    Status/activity timeline. items: list of [
        'title' => string, 'time' => ?string, 'description' => ?string,
        'badge' => ?(HasLabel&HasColor enum), 'actor' => ?string, 'reason' => ?string, 'icon' => ?string ]
--}}
@if (count($items) === 0)
    <x-admin.empty-state icon="clock" :title="__('admin.common.no_results')" compact />
@else
    <ol {{ $attributes->class(['relative space-y-6 border-s border-slate-200 ps-6']) }}>
        @foreach ($items as $item)
            <li class="relative">
                <span class="absolute -start-[1.95rem] top-0.5 inline-flex size-4 items-center justify-center rounded-full bg-white ring-4 ring-white">
                    <span class="size-2.5 rounded-full bg-brand-500"></span>
                </span>
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-sm font-medium text-slate-900">{{ $item['title'] }}</p>
                    @if (! empty($item['badge']))
                        <x-admin.badge :value="$item['badge']" />
                    @endif
                </div>
                @if (! empty($item['time']) || ! empty($item['actor']))
                    <p class="mt-0.5 text-xs text-slate-500">
                        @if (! empty($item['actor'])){{ $item['actor'] }} · @endif
                        @if (! empty($item['time']))<time>{{ $item['time'] }}</time>@endif
                    </p>
                @endif
                @if (! empty($item['description']))
                    <p class="mt-1 text-sm text-slate-600">{{ $item['description'] }}</p>
                @endif
                @if (! empty($item['reason']))
                    <p class="mt-1 rounded bg-slate-50 px-2 py-1 text-sm text-slate-700"><span class="font-medium">{{ __('admin.common.reason') }}:</span> {{ $item['reason'] }}</p>
                @endif
            </li>
        @endforeach
    </ol>
@endif

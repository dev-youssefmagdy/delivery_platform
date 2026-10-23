@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $ago = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
    $infra = collect($items)->reject(fn ($item) => $item->area->isAdapter() || $item->area === \App\Enums\System\HealthArea::PaymentWebhooks);
    $adapters = collect($items)->filter(fn ($item) => $item->area->isAdapter() || $item->area === \App\Enums\System\HealthArea::PaymentWebhooks);
@endphp
<x-admin.layouts.app :title="__('system.health.title')">
    <x-admin.page-header
        :title="__('system.health.title')"
        :subtitle="__('system.health.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('system.health.title')]]"
    >
        <x-admin.button variant="secondary" icon="refresh" :href="route('admin.system.health')">{{ __('system.health.refresh') }}</x-admin.button>
        @can('viewAny', \App\Models\FailedJob::class)
            <x-admin.button variant="secondary" :href="route('admin.failed-jobs.index')">{{ __('system.queues.title') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <div class="mb-6 flex items-center gap-3">
        <span class="text-sm text-slate-600">{{ __('system.health.overall') }}</span>
        <x-admin.badge :value="$overall" />
    </div>

    @foreach (['infrastructure' => $infra, 'adapters' => $adapters] as $group => $rows)
        <x-admin.card :title="__('system.health.groups.'.$group)" class="mb-6">
            <ul class="divide-y divide-slate-100">
                @foreach ($rows as $item)
                    <li class="flex flex-wrap items-start justify-between gap-3 py-3" data-health-area="{{ $item->area->value }}">
                        <div class="min-w-0">
                            <p class="font-medium text-slate-900">{{ $item->area->label() }}</p>
                            <p class="mt-0.5 text-sm text-slate-500">
                                @forelse ($item->details as $name => $value)
                                    @if ($value !== null && $value !== '')
                                        <span class="me-3 inline-block">{{ str_starts_with($name, 'depth_') ? __('system.health.details.depth', ['queue' => substr($name, 6)]) : __('system.health.details.'.$name) }}: <span dir="ltr">{{ $value }}</span></span>
                                    @endif
                                @empty
                                    @if ($item->status === \App\Enums\Common\HealthStatus::NotConfigured) {{ __('system.health.not_configured_hint') }} @endif
                                @endforelse
                            </p>
                            @if ($item->lastError)
                                <p class="mt-1 text-sm text-danger-700">{{ __('system.health.last_error') }}: <code dir="ltr">{{ $item->lastError }}</code>@if ($item->lastErrorAt) <span class="text-slate-500">({{ $ago($item->lastErrorAt) }})</span>@endif</p>
                            @endif
                        </div>
                        <x-admin.badge :value="$item->status" />
                    </li>
                @endforeach
            </ul>
        </x-admin.card>
    @endforeach
</x-admin.layouts.app>

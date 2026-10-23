<x-admin.layouts.app :title="__('reporting.hub.title')">
    <x-admin.page-header :title="__('reporting.hub.title')" :subtitle="__('reporting.hub.subtitle')" :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('reporting.hub.title')]]" />

    @forelse ($groups as $group => $reports)
        <section class="mb-8" aria-labelledby="reports-{{ $group }}">
            <h2 id="reports-{{ $group }}" class="mb-3 text-sm font-semibold tracking-wide text-slate-600 uppercase">{{ __('reporting.hub.groups.'.$group) }}</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($reports as $report)
                    <a href="{{ route('admin.reports.show', $report->value) }}" class="card flex items-start gap-4 p-5 transition hover:border-brand-300">
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-admin.icon :name="$report->icon()" class="size-5" /></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-slate-900">{{ $report->label() }}</span>
                            <span class="mt-1 block text-sm text-slate-600">{{ $report->description() }}</span>
                            <span class="mt-2 inline-flex"><span @class(['badge', 'badge-success' => $report->freshness() === \App\Enums\Reporting\DataFreshness::Live, 'badge-neutral' => $report->freshness() !== \App\Enums\Reporting\DataFreshness::Live])>{{ $report->freshness()->label() }}</span></span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <x-admin.empty-state icon="inbox" :title="__('reporting.hub.empty')" />
    @endforelse
</x-admin.layouts.app>

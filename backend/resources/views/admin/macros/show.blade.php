<x-admin.layouts.app :title="__('admin.macros.show_title')">
    <x-admin.page-header
        :title="$macro->title_ar"
        :subtitle="$macro->category?->label() ?? __('admin.macros.all_categories')"
        :breadcrumbs="[['label' => __('admin.macros.title'), 'url' => route('admin.macros.index')], ['label' => $macro->title_ar]]"
    >
        @can('update', $macro)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.macros.edit', $macro)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.macros.fields.active')">
                        <x-admin.badge :label="$macro->active ? __('admin.common.yes') : __('admin.common.no')" :color="$macro->active ? 'success' : 'neutral'" />
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.macros.fields.category')" :value="$macro->category?->label() ?? __('admin.macros.all_categories')" />
                </x-admin.detail-list>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach (\App\Enums\Common\Locale::cases() as $locale)
                        <div class="rounded-(--radius-control) border border-slate-200 p-4" lang="{{ $locale->value }}" dir="{{ $locale->direction() }}">
                            <p class="mb-2 text-xs font-semibold text-slate-500">{{ $locale->label() }}@if ($locale->value === 'en' && $macro->body_en === null) · {{ __('admin.macros.falls_back') }}@endif</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $macro->titleFor($locale->value) }}</p>
                            <p class="mt-2 text-sm whitespace-pre-line text-slate-700">{{ $macro->bodyFor($locale->value) }}</p>
                        </div>
                    @endforeach
                </div>
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="activity" :url="route('admin.macros.activity', $macro)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>

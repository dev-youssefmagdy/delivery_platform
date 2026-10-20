<x-admin.layouts.app :title="__('admin.service_families.show_title')">
    <x-admin.page-header
        :title="$family->name"
        :breadcrumbs="[['label' => __('admin.service_families.title'), 'url' => route('admin.service-families.index')], ['label' => $family->name]]"
    >
        @can('update', $family)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.service-families.edit', $family)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('activate', $family)
            <x-admin.confirm-action :url="route('admin.service-families.activate', ['serviceFamily' => $family, 'from_show' => 1])" :title="__('admin.catalog.confirm.activate_title')" :message="__('admin.service_families.confirm.activate_message')" :confirm-label="__('admin.catalog.actions.activate')" variant="success" icon="play">{{ __('admin.catalog.actions.activate') }}</x-admin.confirm-action>
        @endcan
        @can('deactivate', $family)
            <x-admin.confirm-action :url="route('admin.service-families.deactivate', ['serviceFamily' => $family, 'from_show' => 1])" :title="__('admin.catalog.confirm.deactivate_title')" :message="__('admin.service_families.confirm.deactivate_message')" :confirm-label="__('admin.catalog.actions.deactivate')" reason-required icon="pause">{{ __('admin.catalog.actions.deactivate') }}</x-admin.confirm-action>
        @endcan
        @can('delete', $family)
            <x-admin.confirm-action :url="route('admin.service-families.destroy', ['serviceFamily' => $family, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" reason-required icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @unless ($family->active)
        <x-admin.alert type="warning" class="mb-6">{{ __('admin.catalog.inactive_notice') }}</x-admin.alert>
    @endunless

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.catalog.fields.code')"><span class="ltr-only">{{ $family->code }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.service_families.fields.kind')" :value="$family->kind->label()" />
                    <x-admin.detail-list.item :label="__('admin.service_families.fields.name').' ('.\App\Enums\Common\Locale::Arabic->label().')'" :value="$family->getTranslation('name', 'ar', false)" />
                    <x-admin.detail-list.item :label="__('admin.service_families.fields.name').' ('.\App\Enums\Common\Locale::English->label().')'" :value="$family->getTranslation('name', 'en', false)" />
                    <x-admin.detail-list.item :label="__('admin.service_families.fields.description')" :value="$family->getTranslation('description', app()->getLocale(), true)" />
                    <x-admin.detail-list.item :label="__('admin.catalog.fields.icon')"><span class="ltr-only">{{ $family->icon ?? '—' }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.catalog.fields.sort_order')" :value="$family->sort_order" />
                    <x-admin.detail-list.item :label="__('admin.service_families.fields.categories')" :value="$family->categories_count" />
                    <x-admin.detail-list.item :label="__('admin.catalog.fields.active')">
                        <x-admin.badge :label="$family->active ? __('admin.common.yes') : __('admin.common.no')" :color="$family->active ? 'success' : 'neutral'" />
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.common.created_at')" :value="$family->created_at->copy()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')" />
                </x-admin.detail-list>
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="activity" :url="route('admin.service-families.activity', $family)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>

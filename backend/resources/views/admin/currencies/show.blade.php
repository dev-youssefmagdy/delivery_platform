<x-admin.layouts.app :title="__('admin.currencies.show_title')">
    <x-admin.page-header
        :title="$currency->code"
        :breadcrumbs="[['label' => __('admin.currencies.title'), 'url' => route('admin.currencies.index')], ['label' => $currency->code]]"
    >
        @can('update', $currency)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.currencies.edit', $currency)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('delete', $currency)
            <x-admin.confirm-action :url="route('admin.currencies.destroy', ['currency' => $currency, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" reason-required icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.currencies.fields.code')"><span class="ltr-only">{{ $currency->code }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.currencies.fields.symbol_ar')" :value="$currency->symbol_ar" />
                    <x-admin.detail-list.item :label="__('admin.currencies.fields.symbol_en')"><span class="ltr-only">{{ $currency->symbol_en }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.currencies.fields.exponent')"><span class="ltr-only">{{ $currency->exponent }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.currencies.fields.active')">
                        <x-admin.badge :label="$currency->active ? __('admin.common.yes') : __('admin.common.no')" :color="$currency->active ? 'success' : 'neutral'" />
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.common.created_at')" :value="$currency->created_at->copy()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')" />
                </x-admin.detail-list>
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="activity" :url="route('admin.currencies.activity', $currency)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>

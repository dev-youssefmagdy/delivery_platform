<x-admin.layouts.app :title="__('admin.countries.show_title')">
    <x-admin.page-header
        :title="$country->name"
        :breadcrumbs="[['label' => __('admin.countries.title'), 'url' => route('admin.countries.index')], ['label' => $country->name]]"
    >
        @can('update', $country)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.countries.edit', $country)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('delete', $country)
            <x-admin.confirm-action :url="route('admin.countries.destroy', ['country' => $country, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" reason-required icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.countries.fields.iso2')"><span class="ltr-only">{{ $country->iso2 }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.countries.fields.name').' ('.\App\Enums\Common\Locale::Arabic->label().')'" :value="$country->getTranslation('name', 'ar', false)" />
                    <x-admin.detail-list.item :label="__('admin.countries.fields.name').' ('.\App\Enums\Common\Locale::English->label().')'" :value="$country->getTranslation('name', 'en', false)" />
                    <x-admin.detail-list.item :label="__('admin.countries.fields.phone_code')"><span class="ltr-only">{{ $country->phone_code }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.countries.fields.currency')" :value="$country->currency?->optionLabel() ?? $country->currency_code" />
                    <x-admin.detail-list.item :label="__('admin.countries.fields.timezone')"><span class="ltr-only">{{ $country->timezone }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.countries.fields.default_locale')" :value="\App\Enums\Common\Locale::tryFromValue($country->default_locale)?->label()" />
                    <x-admin.detail-list.item :label="__('admin.countries.fields.active')">
                        <x-admin.badge :label="$country->active ? __('admin.common.yes') : __('admin.common.no')" :color="$country->active ? 'success' : 'neutral'" />
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.common.created_at')" :value="$country->created_at->copy()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')" />
                </x-admin.detail-list>
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="activity" :url="route('admin.countries.activity', $country)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>

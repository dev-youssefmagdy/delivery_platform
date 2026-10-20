<x-admin.layouts.app :title="__('admin.document_types.show_title')">
    <x-admin.page-header
        :title="$type->name"
        :breadcrumbs="[['label' => __('admin.document_types.title'), 'url' => route('admin.document-types.index')], ['label' => $type->name]]"
    >
        @can('update', $type)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.document-types.edit', $type)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('activate', $type)
            <x-admin.confirm-action :url="route('admin.document-types.activate', ['documentType' => $type, 'from_show' => 1])" :title="__('admin.catalog.confirm.activate_title')" :message="__('admin.document_types.confirm.activate_message')" :confirm-label="__('admin.catalog.actions.activate')" variant="success" icon="play">{{ __('admin.catalog.actions.activate') }}</x-admin.confirm-action>
        @endcan
        @can('deactivate', $type)
            <x-admin.confirm-action :url="route('admin.document-types.deactivate', ['documentType' => $type, 'from_show' => 1])" :title="__('admin.catalog.confirm.deactivate_title')" :message="__('admin.document_types.confirm.deactivate_message')" :confirm-label="__('admin.catalog.actions.deactivate')" reason-required icon="pause">{{ __('admin.catalog.actions.deactivate') }}</x-admin.confirm-action>
        @endcan
        @can('delete', $type)
            <x-admin.confirm-action :url="route('admin.document-types.destroy', ['documentType' => $type, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" reason-required icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @unless ($type->active)
        <x-admin.alert type="warning" class="mb-6">{{ __('admin.document_types.inactive_notice') }}</x-admin.alert>
    @endunless

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'requirements' => ['label' => __('admin.document_types.tabs.requirements'), 'icon' => 'clipboard'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.catalog.fields.code')"><span class="ltr-only">{{ $type->code }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.document_types.fields.name').' ('.\App\Enums\Common\Locale::Arabic->label().')'" :value="$type->getTranslation('name', 'ar', false)" />
                    <x-admin.detail-list.item :label="__('admin.document_types.fields.name').' ('.\App\Enums\Common\Locale::English->label().')'" :value="$type->getTranslation('name', 'en', false)" />
                    <x-admin.detail-list.item :label="__('admin.document_types.fields.description')" :value="$type->getTranslation('description', app()->getLocale(), true)" />
                    <x-admin.detail-list.item :label="__('admin.document_types.fields.applies_to')" :value="$type->applies_to->label()" />
                    <x-admin.detail-list.item :label="__('admin.document_types.fields.requires_expiry')"><x-admin.badge :label="$type->requires_expiry ? __('admin.common.yes') : __('admin.common.no')" :color="$type->requires_expiry ? 'success' : 'neutral'" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.document_types.fields.requires_number')"><x-admin.badge :label="$type->requires_number ? __('admin.common.yes') : __('admin.common.no')" :color="$type->requires_number ? 'success' : 'neutral'" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.document_types.fields.allowed_mimes')"><span class="ltr-only">{{ implode(', ', $type->allowed_mimes) }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.document_types.fields.max_size_kb')" :value="$type->max_size_kb.' KB'" />
                    <x-admin.detail-list.item :label="__('admin.document_types.fields.requirements')" :value="$type->requirements_count" />
                    <x-admin.detail-list.item :label="__('admin.catalog.fields.active')"><x-admin.badge :label="$type->active ? __('admin.common.yes') : __('admin.common.no')" :color="$type->active ? 'success' : 'neutral'" /></x-admin.detail-list.item>
                </x-admin.detail-list>
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="requirements" :url="route('admin.document-types.requirements', $type)" />
            <x-admin.tabs.panel id="activity" :url="route('admin.document-types.activity', $type)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>

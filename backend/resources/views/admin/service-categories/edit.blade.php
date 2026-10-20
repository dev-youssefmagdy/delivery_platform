<x-admin.layouts.app :title="__('admin.service_categories.edit_title')">
    <x-admin.page-header
        :title="__('admin.service_categories.edit_title')"
        :breadcrumbs="[['label' => __('admin.service_categories.title'), 'url' => route('admin.service-categories.index')], ['label' => $category->name, 'url' => route('admin.service-categories.show', $category)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.alert type="info" class="mb-6">{{ __('admin.service_categories.subtitle') }}</x-admin.alert>

    <x-admin.card>
        <x-admin.form :action="route('admin.service-categories.update', $category)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $category->lock_version }}">
            @include('admin.service-categories._form', ['model' => $category])
            <x-admin.form.actions :cancel="route('admin.service-categories.show', $category)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

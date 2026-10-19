<x-admin.layouts.app :title="__('admin.countries.create_title')">
    <x-admin.page-header
        :title="__('admin.countries.create_title')"
        :breadcrumbs="[['label' => __('admin.countries.title'), 'url' => route('admin.countries.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.countries.store')" method="POST">
            @include('admin.countries._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.countries.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

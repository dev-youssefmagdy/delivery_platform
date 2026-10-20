<x-admin.layouts.app :title="__('admin.document_types.create_title')">
    <x-admin.page-header
        :title="__('admin.document_types.create_title')"
        :breadcrumbs="[['label' => __('admin.document_types.title'), 'url' => route('admin.document-types.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.document-types.store')" method="POST">
            @include('admin.document-types._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.document-types.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

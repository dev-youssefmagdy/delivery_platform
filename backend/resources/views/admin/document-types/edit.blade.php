<x-admin.layouts.app :title="__('admin.document_types.edit_title')">
    <x-admin.page-header
        :title="__('admin.document_types.edit_title')"
        :breadcrumbs="[['label' => __('admin.document_types.title'), 'url' => route('admin.document-types.index')], ['label' => $type->name, 'url' => route('admin.document-types.show', $type)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.document-types.update', $type)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $type->lock_version }}">
            @include('admin.document-types._form', ['model' => $type])
            <x-admin.form.actions :cancel="route('admin.document-types.show', $type)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

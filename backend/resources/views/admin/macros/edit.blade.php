<x-admin.layouts.app :title="__('admin.macros.edit_title')">
    <x-admin.page-header
        :title="__('admin.macros.edit_title')"
        :breadcrumbs="[['label' => __('admin.macros.title'), 'url' => route('admin.macros.index')], ['label' => $macro->title_ar, 'url' => route('admin.macros.show', $macro)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.macros.update', $macro)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $macro->lock_version }}">
            @include('admin.macros._form', ['model' => $macro])
            <x-admin.form.actions :cancel="route('admin.macros.show', $macro)" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

@php($demoItem = $details->item)
<x-admin.layouts.app :title="__('admin.demo_items.edit_title')">
    <x-admin.page-header
        :title="__('admin.demo_items.edit_title')"
        :breadcrumbs="[['label' => __('admin.demo_items.title'), 'url' => route('admin.demo-items.index')], ['label' => '#'.$demoItem->id, 'url' => route('admin.demo-items.show', $demoItem)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.demo-items.update', $demoItem)" method="PUT" :confirm="__('admin.demo_items.confirm_save')">
            <input type="hidden" name="lock_version" value="{{ $demoItem->lock_version }}">
            @include('admin.demo-items._form', ['item' => $demoItem, 'coverUrl' => $details->coverUrl])
            <x-admin.form.actions :cancel="route('admin.demo-items.show', $demoItem)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

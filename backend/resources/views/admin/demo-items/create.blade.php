<x-admin.layouts.app :title="__('admin.demo_items.create_title')">
    <x-admin.page-header
        :title="__('admin.demo_items.create_title')"
        :breadcrumbs="[['label' => __('admin.demo_items.title'), 'url' => route('admin.demo-items.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.demo-items.store')" method="POST">
            @include('admin.demo-items._form', ['item' => null, 'coverUrl' => null])
            <x-admin.form.actions :cancel="route('admin.demo-items.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

<x-admin.layouts.app :title="__('admin.coupons.create_title')">
    <x-admin.page-header
        :title="__('admin.coupons.create_title')"
        :breadcrumbs="[['label' => __('admin.coupons.title'), 'url' => route('admin.coupons.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.coupons.store')" method="POST">
            @include('admin.coupons._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.coupons.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

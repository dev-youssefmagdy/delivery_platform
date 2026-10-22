<x-admin.layouts.app :title="__('admin.push_campaigns.create_title')">
    <x-admin.page-header
        :title="__('admin.push_campaigns.create_title')"
        :breadcrumbs="[['label' => __('admin.push_campaigns.title'), 'url' => route('admin.push-campaigns.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.push-campaigns.store')" method="POST">
            @include('admin.push-campaigns._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.push-campaigns.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

<x-admin.layouts.app :title="__('admin.push_campaigns.edit_title')">
    <x-admin.page-header
        :title="__('admin.push_campaigns.edit_title')"
        :breadcrumbs="[['label' => __('admin.push_campaigns.title'), 'url' => route('admin.push-campaigns.index')], ['label' => $campaign->name, 'url' => route('admin.push-campaigns.show', $campaign)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.push-campaigns.update', $campaign)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $campaign->lock_version }}">
            @include('admin.push-campaigns._form', ['model' => $campaign])
            <x-admin.form.actions :cancel="route('admin.push-campaigns.show', $campaign)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

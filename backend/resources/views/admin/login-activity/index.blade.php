<x-admin.layouts.app :title="__('admin.login_activity.title')">
    <x-admin.page-header
        :title="__('admin.login_activity.title')"
        :subtitle="__('admin.login_activity.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.login_activity.title')]]"
    />

    <x-admin.filters-card for="login-activity-table">
        <x-admin.filters.text name="email" :label="__('admin.login_activity.fields.email')" />
        <x-admin.filters.enum name="outcomes" :label="__('admin.login_activity.fields.outcome')" :enum="\App\Enums\Admin\AdminLoginOutcome::class" multiple />
        <x-admin.filters.text name="ip" :label="__('admin.login_activity.fields.ip')" />
        <x-admin.filters.date-range name="created_at" :label="__('admin.login_activity.fields.time')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>

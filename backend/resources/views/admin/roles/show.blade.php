<x-admin.layouts.app :title="__('admin.roles.show_title')">
    <x-admin.page-header
        :title="$role->displayName()"
        :breadcrumbs="[['label' => __('admin.roles.title'), 'url' => route('admin.roles.index')], ['label' => $role->displayName()]]"
    >
        @can('update', $role)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.roles.edit', $role)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('delete', $role)
            <x-admin.confirm-action :url="route('admin.roles.destroy', ['role' => $role, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" reason-required icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'permissions' => ['label' => __('admin.roles.sections.permissions'), 'icon' => 'key'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.roles.fields.label')" :value="$role->displayName()" />
                    <x-admin.detail-list.item :label="__('admin.roles.fields.name')"><span class="ltr-only">{{ $role->name }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.roles.fields.type')">
                        <x-admin.badge :label="$role->is_system ? __('admin.roles.type.system') : __('admin.roles.type.custom')" :color="$role->is_system ? 'info' : 'neutral'" />
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.common.created_at')" :value="$role->created_at?->copy()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')" />
                </x-admin.detail-list>
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="permissions">
                @if ($role->isSuperAdmin())
                    <x-admin.alert type="info">{{ __('admin.roles.super_admin_note') }}</x-admin.alert>
                @elseif ($matrix['selected'] === [])
                    <span class="text-sm text-slate-500">{{ __('admin.roles.no_permissions') }}</span>
                @else
                    <x-admin.permission-matrix :selected="$matrix['selected']" readonly />
                @endif
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="activity" :url="route('admin.roles.activity', $role)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>

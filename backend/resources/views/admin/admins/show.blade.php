@php($admin = $details->admin)
<x-admin.layouts.app :title="__('admin.admins.show_title')">
    <x-admin.page-header
        :title="$admin->name"
        :breadcrumbs="[['label' => __('admin.admins.title'), 'url' => route('admin.admins.index')], ['label' => $admin->name]]"
    >
        @can('update', $admin)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.admins.edit', $admin)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('suspend', $admin)
            <x-admin.confirm-action
                :url="route('admin.admins.suspend', ['admin' => $admin, 'from_show' => 1])"
                :title="__('admin.admins.confirm.suspend_title')"
                :message="__('admin.admins.confirm.suspend_message')"
                :confirm-label="__('admin.admins.actions.suspend')"
                reason-required
                variant="warning"
                icon="pause"
            >{{ __('admin.admins.actions.suspend') }}</x-admin.confirm-action>
        @endcan
        @can('reactivate', $admin)
            <x-admin.confirm-action
                :url="route('admin.admins.reactivate', ['admin' => $admin, 'from_show' => 1])"
                :title="__('admin.admins.confirm.reactivate_title')"
                :message="__('admin.admins.confirm.reactivate_message')"
                :confirm-label="__('admin.admins.actions.reactivate')"
                variant="success"
                icon="play"
            >{{ __('admin.admins.actions.reactivate') }}</x-admin.confirm-action>
        @endcan
        @can('resetMfa', $admin)
            <x-admin.confirm-action
                :url="route('admin.admins.reset-mfa', ['admin' => $admin, 'from_show' => 1])"
                :title="__('admin.admins.confirm.reset_mfa_title')"
                :message="__('admin.admins.confirm.reset_mfa_message')"
                :confirm-label="__('admin.admins.actions.reset_mfa')"
                reason-required
                variant="warning"
                icon="lock-closed"
            >{{ __('admin.admins.actions.reset_mfa') }}</x-admin.confirm-action>
        @endcan
        @can('delete', $admin)
            <x-admin.confirm-action :url="route('admin.admins.destroy', ['admin' => $admin, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" reason-required icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'access' => ['label' => __('admin.admins.sections.access'), 'icon' => 'key'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item label="#" :value="$admin->id" />
                    <x-admin.detail-list.item :label="__('admin.admins.fields.name')" :value="$admin->name" />
                    <x-admin.detail-list.item :label="__('admin.admins.fields.email')"><span class="ltr-only">{{ $admin->email }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.admins.fields.phone')"><span class="ltr-only">{{ $admin->phone }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.admins.fields.locale')" :value="\App\Enums\Common\Locale::tryFromValue($admin->locale)?->label()" />
                    <x-admin.detail-list.item :label="__('admin.admins.fields.status')"><x-admin.badge :value="$admin->status" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.admins.fields.mfa')">
                        <x-admin.badge :label="$admin->hasMfaEnabled() ? __('admin.security.mfa_on') : __('admin.security.mfa_off')" :color="$admin->hasMfaEnabled() ? 'success' : 'neutral'" />
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.admins.fields.last_login')" :value="$admin->last_login_at?->copy()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')" />
                    <x-admin.detail-list.item :label="__('admin.common.created_at')" :value="$admin->created_at->copy()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')" />
                </x-admin.detail-list>
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="access">
                <h3 class="mb-2 text-sm font-semibold text-slate-900">{{ __('admin.admins.fields.roles') }}</h3>
                <div class="mb-6 flex flex-wrap gap-2">
                    @forelse ($details->roles as $role)
                        <x-admin.badge :label="$role->displayName()" color="info" :dot="false" />
                    @empty
                        <span class="text-sm text-slate-500">{{ __('admin.admins.no_roles') }}</span>
                    @endforelse
                </div>

                <h3 class="mb-2 text-sm font-semibold text-slate-900">{{ __('admin.admins.effective_permissions') }}</h3>
                @if ($details->isSuperAdmin)
                    <x-admin.alert type="info">{{ __('admin.admins.super_admin_note') }}</x-admin.alert>
                @elseif ($details->permissionNames === [])
                    <span class="text-sm text-slate-500">{{ __('admin.admins.no_permissions') }}</span>
                @else
                    <x-admin.permission-matrix :selected="$details->permissionNames" readonly />
                @endif
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="activity" :url="route('admin.admins.activity', $admin)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>

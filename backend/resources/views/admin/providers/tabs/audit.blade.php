{{-- Audit tab: the current standing and the audit trail of every admin action on this provider. --}}
<x-admin.detail-list class="mb-6">
    <x-admin.detail-list.item :label="__('admin.providers.fields.status')">
        <x-admin.badge :value="$provider->status" />
    </x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.providers.fields.status_reason')" :value="$provider->status_reason" />
    <x-admin.detail-list.item :label="__('admin.providers.fields.status_changed')" :value="$provider->status_changed_at?->copy()->setTimezone(config('admin.display_timezone'))->translatedFormat('Y-m-d H:i')" />
    <x-admin.detail-list.item :label="__('admin.providers.fields.suspension_ends')" :value="$provider->status_expires_at?->copy()->setTimezone(config('admin.display_timezone'))->translatedFormat('Y-m-d H:i')" />
</x-admin.detail-list>
<p class="mb-4 text-sm text-slate-500">{{ __('admin.providers.audit.intro') }}</p>
<x-admin.datatable :table="$dataTable" />

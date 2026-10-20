{{-- Restrictions and audit tab: the current account standing and the audit trail of every admin action. --}}
@php($user = $customer)
<x-admin.detail-list class="mb-6">
    <x-admin.detail-list.item :label="__('admin.customers.fields.status')">
        <x-admin.badge :value="$user->status" />
    </x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.customers.fields.status_reason')" :value="$user->status_reason" />
    <x-admin.detail-list.item :label="__('admin.customers.fields.status_changed')" :value="$user->status_changed_at?->copy()->setTimezone(config('admin.display_timezone'))->translatedFormat('Y-m-d H:i')" />
    <x-admin.detail-list.item :label="__('admin.customers.fields.suspension_ends')" :value="$user->status_expires_at?->copy()->setTimezone(config('admin.display_timezone'))->translatedFormat('Y-m-d H:i')" />
</x-admin.detail-list>
<p class="mb-4 text-sm text-slate-500">{{ __('admin.customers.audit.intro') }}</p>
<x-admin.datatable :table="$dataTable" />

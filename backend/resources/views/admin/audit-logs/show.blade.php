@php($log = $details->log)
<x-admin.layouts.app :title="__('admin.audit_logs.show_title')">
    <x-admin.page-header
        :title="__('admin.audit_logs.show_title').' #'.$log->id"
        :breadcrumbs="[['label' => __('admin.audit_logs.title'), 'url' => route('admin.audit-logs.index')], ['label' => '#'.$log->id]]"
    />

    <div class="space-y-6">
        <x-admin.card :title="__('admin.common.details')">
            <x-admin.detail-list>
                <x-admin.detail-list.item :label="__('admin.audit_logs.fields.time')" :value="$log->created_at->copy()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i:s')" />
                <x-admin.detail-list.item :label="__('admin.audit_logs.fields.actor')" :value="$details->actorName" />
                <x-admin.detail-list.item :label="__('admin.audit_logs.fields.action')"><x-admin.badge :value="$log->action" /></x-admin.detail-list.item>
                <x-admin.detail-list.item :label="__('admin.audit_logs.fields.target_type')"><span class="ltr-only">{{ $log->auditable_type }}</span></x-admin.detail-list.item>
                <x-admin.detail-list.item :label="__('admin.audit_logs.fields.target_id')"><span class="ltr-only">{{ $log->auditable_id }}</span></x-admin.detail-list.item>
                <x-admin.detail-list.item :label="__('admin.audit_logs.fields.ip')"><span class="ltr-only">{{ $log->ip }}</span></x-admin.detail-list.item>
                <x-admin.detail-list.item :label="__('admin.audit_logs.fields.request_id')"><span class="ltr-only">{{ $log->request_id }}</span></x-admin.detail-list.item>
                <x-admin.detail-list.item :label="__('admin.audit_logs.fields.user_agent')" :value="$log->user_agent" full />
                <x-admin.detail-list.item :label="__('admin.audit_logs.fields.reason')" :value="$log->reason" full />
            </x-admin.detail-list>
        </x-admin.card>

        <x-admin.card :title="__('admin.audit_logs.changes')" :subtitle="__('admin.audit_logs.changes_hint')">
            <x-admin.diff-viewer :rows="$details->rows" />
        </x-admin.card>

        @if ($log->meta)
            <x-admin.card :title="__('admin.audit_logs.meta')">
                <x-admin.json-viewer :data="$log->meta" />
            </x-admin.card>
        @endif
    </div>
</x-admin.layouts.app>

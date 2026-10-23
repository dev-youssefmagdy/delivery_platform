@props(['content', 'type'])
{{--
    Show-page body shared by all workflow content: status notice, a details tab (the slot holds the entity-specific rows,
    the common workflow rows follow), lazy-loaded revision history and audit activity.
--}}
@php
    use App\Enums\Content\ContentWorkflowStatus;

    $prefix = $type->routePrefix();
    $tz = (string) config('admin.display_timezone');
    $when = fn ($value) => $value?->copy()->setTimezone($tz)->format('Y-m-d H:i');
@endphp
<x-admin.alert :type="match ($content->status) { ContentWorkflowStatus::Published => 'success', ContentWorkflowStatus::Archived => 'warning', default => 'info' }" class="mb-6">
    {{ __('content.status_notice.'.$content->status->value, ['at' => $when($content->scheduled_at)]) }}
</x-admin.alert>

<x-admin.card :padding="false" class="p-5">
    <x-admin.tabs :tabs="[
        'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
        'revisions' => ['label' => __('content.tabs.revisions'), 'icon' => 'clock'],
        'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clipboard'],
    ]">
        <x-admin.tabs.panel id="details">
            <x-admin.detail-list>
                <x-admin.detail-list.item :label="__('content.fields.status')"><x-admin.badge :value="$content->status" /></x-admin.detail-list.item>
                {{ $slot }}
                <x-admin.detail-list.item :label="__('content.fields.scheduled_at')" :value="$when($content->scheduled_at)" />
                <x-admin.detail-list.item :label="__('content.fields.published_at')" :value="$when($content->published_at)" />
                <x-admin.detail-list.item :label="__('content.fields.published_by')" :value="$content->publisher?->name" />
                <x-admin.detail-list.item :label="__('content.fields.created_by')" :value="$content->creator?->name" />
                <x-admin.detail-list.item :label="__('content.fields.updated_by')" :value="$content->updater?->name" />
                <x-admin.detail-list.item :label="__('admin.common.created_at')" :value="$when($content->created_at)" />
                <x-admin.detail-list.item :label="__('admin.common.updated_at')" :value="$when($content->updated_at)" />
            </x-admin.detail-list>
        </x-admin.tabs.panel>
        <x-admin.tabs.panel id="revisions" :url="route('admin.content.revisions', ['contentType' => $type->value, 'content' => $content->getKey()])" />
        <x-admin.tabs.panel id="activity" :url="route($prefix.'.activity', $content)" />
    </x-admin.tabs>
</x-admin.card>

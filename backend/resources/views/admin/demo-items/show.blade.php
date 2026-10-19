@php($demoItem = $details->item)
<x-admin.layouts.app :title="__('admin.demo_items.show_title')">
    <x-admin.page-header
        :title="$demoItem->name"
        :breadcrumbs="[['label' => __('admin.demo_items.title'), 'url' => route('admin.demo-items.index')], ['label' => '#'.$demoItem->id]]"
    >
        @can('update', $demoItem)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.demo-items.edit', $demoItem)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('archive', $demoItem)
            <x-admin.confirm-action
                :url="route('admin.demo-items.archive', ['demoItem' => $demoItem, 'from_show' => 1])"
                :title="__('admin.demo_items.archive_title')"
                :message="__('admin.demo_items.archive_message')"
                :confirm-label="__('admin.demo_items.archive')"
                reason-required
                variant="warning"
                icon="archive"
            >{{ __('admin.demo_items.archive') }}</x-admin.confirm-action>
        @endcan
        @can('delete', $demoItem)
            <x-admin.confirm-action :url="route('admin.demo-items.destroy', ['demoItem' => $demoItem, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($demoItem->status === \App\Enums\Demo\DemoItemStatus::Archived)
        <x-admin.alert type="warning" :title="__('admin.demo_items.archived_notice')" class="mb-6">
            {{ $demoItem->archived_reason }}
        </x-admin.alert>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <x-admin.card :padding="false" class="p-5 xl:col-span-2">
            <x-admin.tabs :tabs="[
                'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
                'location' => ['label' => __('admin.demo_items.sections.location'), 'icon' => 'map'],
                'data' => ['label' => __('admin.demo_items.sections.details'), 'icon' => 'code'],
                'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
            ]">
                <x-admin.tabs.panel id="details">
                    @include('admin.demo-items._details', ['item' => $demoItem, 'coverUrl' => $details->coverUrl])
                </x-admin.tabs.panel>
                <x-admin.tabs.panel id="location">
                    <x-admin.map-preview :lat="$demoItem->latitude" :lng="$demoItem->longitude" :polygon="$demoItem->area" :label="__('admin.demo_items.fields.location')" />
                </x-admin.tabs.panel>
                <x-admin.tabs.panel id="data">
                    <div class="space-y-4">
                        <div>
                            <p class="form-label">{{ __('admin.demo_items.fields.specs') }}</p>
                            <x-admin.json-viewer :data="$demoItem->specs ?? []" />
                        </div>
                        <div>
                            <p class="form-label">{{ __('admin.demo_items.fields.options') }}</p>
                            <x-admin.json-viewer :data="$demoItem->options ?? []" collapsed />
                        </div>
                    </div>
                </x-admin.tabs.panel>
                <x-admin.tabs.panel id="activity" :url="route('admin.demo-items.activity', $demoItem)" />
            </x-admin.tabs>
        </x-admin.card>

        <div class="space-y-6">
            <x-admin.card :title="__('admin.demo_items.fields.cover')">
                @if ($details->coverUrl)
                    <img src="{{ $details->coverUrl }}" alt="{{ $demoItem->name }}" class="w-full rounded-(--radius-control) object-cover">
                @else
                    <x-admin.empty-state icon="photo" :title="__('admin.common.not_set')" compact />
                @endif
            </x-admin.card>
            <x-admin.card :title="__('admin.demo_items.sections.meta')">
                <x-admin.detail-list :cols="1">
                    <x-admin.detail-list.item :label="__('admin.common.created_at')" :value="$demoItem->created_at->copy()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')" />
                    <x-admin.detail-list.item :label="__('admin.common.updated_at')" :value="$demoItem->updated_at->copy()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')" />
                    <x-admin.detail-list.item :label="__('admin.demo_items.fields.lock_version')" :value="$demoItem->lock_version" />
                </x-admin.detail-list>
            </x-admin.card>
        </div>
    </div>
</x-admin.layouts.app>

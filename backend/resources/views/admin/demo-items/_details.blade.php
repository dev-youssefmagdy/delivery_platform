{{-- Detail list shared by the show page and the drawer quick view. $item: DemoItem, $coverUrl: ?string --}}
<x-admin.detail-list :cols="$cols ?? 2">
    <x-admin.detail-list.item label="#" :value="$item->id" />
    <x-admin.detail-list.item :label="__('admin.demo_items.fields.code')"><span class="ltr-only font-mono">{{ $item->code }}</span></x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.demo_items.fields.name').' (ar)'" :value="$item->getTranslation('name', 'ar', false)" />
    <x-admin.detail-list.item :label="__('admin.demo_items.fields.name').' (en)'" :value="$item->getTranslation('name', 'en', false)" />
    <x-admin.detail-list.item :label="__('admin.demo_items.fields.type')"><x-admin.badge :value="$item->type" /></x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.demo_items.fields.status')"><x-admin.badge :value="$item->status" /></x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.demo_items.fields.price')"><x-admin.money :value="$item->price" /></x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.demo_items.fields.country')" :value="$item->country?->name" />
    <x-admin.detail-list.item :label="__('admin.demo_items.fields.tags')">
        @forelse ($item->tags ?? [] as $tag)
            <x-admin.badge :label="$tag->label()" color="primary" :dot="false" />
        @empty
            <span class="text-slate-500">{{ __('admin.common.not_set') }}</span>
        @endforelse
    </x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.demo_items.fields.is_featured')" :value="$item->is_featured ? __('admin.common.yes') : __('admin.common.no')" />
    <x-admin.detail-list.item :label="__('admin.demo_items.fields.available_on')" :value="$item->available_on?->toDateString()" />
    <x-admin.detail-list.item :label="__('admin.demo_items.fields.published_at')" :value="$item->published_at?->copy()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')" />
    <x-admin.detail-list.item :label="__('admin.demo_items.fields.description')" :value="$item->description" full />
</x-admin.detail-list>

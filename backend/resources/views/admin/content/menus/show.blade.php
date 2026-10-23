@php($type = \App\Enums\Content\ContentType::Menu)
<x-admin.layouts.app :title="__('content.menus.show_title')">
    <x-admin.page-header
        :title="$menu->title"
        :breadcrumbs="[['label' => __('content.menus.title'), 'url' => route('admin.content-menus.index')], ['label' => $menu->title]]"
    >
        <x-admin.content.actions :content="$menu" :type="$type" />
    </x-admin.page-header>

    <x-admin.content.tabs :content="$menu" :type="$type">
        <x-admin.detail-list.item :label="__('content.fields.location')" :value="$menu->location->label()" />
        <x-admin.detail-list.item :label="__('content.fields.title')" :value="$menu->getTranslation('title', 'ar', false)" />
        <x-admin.detail-list.item :label="__('content.fields.items')" full>
            <ul class="space-y-1">
                @foreach ((array) $menu->items as $item)
                    <li class="flex flex-wrap gap-x-3"><span>{{ $item['label']['ar'] ?? '' }}</span><span class="text-slate-500" dir="ltr">{{ $item['label']['en'] ?? '' }}</span><span class="text-slate-400" dir="ltr">{{ $item['url'] ?? '' }}</span></li>
                @endforeach
            </ul>
        </x-admin.detail-list.item>
    </x-admin.content.tabs>
</x-admin.layouts.app>

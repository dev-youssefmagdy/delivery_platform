@php
    $title = $menu ? __('content.menus.edit_title') : __('content.menus.create_title');
    $rows = collect($menu?->items ?? [])->map(fn (array $item): array => [
        'label_ar' => $item['label']['ar'] ?? null,
        'label_en' => $item['label']['en'] ?? null,
        'url' => $item['url'] ?? null,
        'new_tab' => (bool) ($item['new_tab'] ?? false),
    ])->all();
@endphp
<x-admin.layouts.app :title="$title">
    <x-admin.page-header
        :title="$title"
        :breadcrumbs="array_filter([
            ['label' => __('content.menus.title'), 'url' => route('admin.content-menus.index')],
            $menu ? ['label' => $menu->title, 'url' => route('admin.content-menus.show', $menu)] : null,
            ['label' => $menu ? __('admin.common.edit') : __('admin.common.create')],
        ])"
    />

    <x-admin.card>
        <x-admin.form :action="$menu ? route('admin.content-menus.update', $menu) : route('admin.content-menus.store')" :method="$menu ? 'PUT' : 'POST'">
            @if ($menu)
                <input type="hidden" name="lock_version" value="{{ $menu->lock_version }}">
            @endif
            <x-admin.form.section :title="__('content.sections.basics')">
                <x-admin.form.enum-select name="location" :label="__('content.fields.location')" :enum="\App\Enums\Content\MenuLocation::class" :value="$menu?->location" :hint="__('content.hints.location')" required />
                <x-admin.form.translatable-input name="title" :label="__('content.fields.title')" :value="$menu?->getTranslations('title') ?? []" maxlength="191" required />
                <x-admin.content.fallback-toggle :model="$menu" />
            </x-admin.form.section>

            <x-admin.form.section :title="__('content.fields.items')" :description="__('content.sections.items_hint')">
                <x-admin.form.repeater
                    name="items"
                    :items="$rows"
                    :min="1"
                    :max="30"
                    :add-label="__('content.menus.add_item')"
                    :fields="[
                        ['name' => 'label_ar', 'label' => __('content.fields.item_label').' (AR)', 'required' => true, 'attributes' => ['dir' => 'rtl', 'maxlength' => 80]],
                        ['name' => 'label_en', 'label' => __('content.fields.item_label').' (EN)', 'attributes' => ['dir' => 'ltr', 'maxlength' => 80]],
                        ['name' => 'url', 'label' => __('content.fields.item_url'), 'required' => true, 'class' => 'min-w-60 flex-1', 'attributes' => ['dir' => 'ltr', 'maxlength' => 500]],
                        ['name' => 'new_tab', 'label' => __('content.fields.new_tab'), 'type' => 'checkbox'],
                    ]"
                />
            </x-admin.form.section>

            <x-admin.form.actions :cancel="$menu ? route('admin.content-menus.show', $menu) : route('admin.content-menus.index')" :submit="__('content.save_draft')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

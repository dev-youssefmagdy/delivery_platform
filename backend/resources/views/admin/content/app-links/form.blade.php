@php($title = $link ? __('content.app_links.edit_title') : __('content.app_links.create_title'))
<x-admin.layouts.app :title="$title">
    <x-admin.page-header
        :title="$title"
        :breadcrumbs="[['label' => __('content.app_links.title'), 'url' => route('admin.content-app-links.index')], ['label' => $link ? __('admin.common.edit') : __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="$link ? route('admin.content-app-links.update', $link) : route('admin.content-app-links.store')" :method="$link ? 'PUT' : 'POST'">
            @if ($link)
                <input type="hidden" name="lock_version" value="{{ $link->lock_version }}">
            @endif
            <x-admin.form.section :title="__('content.sections.basics')">
                <x-admin.form.enum-select name="app" :label="__('content.fields.app')" :enum="\App\Enums\Content\AppKind::class" :value="$link?->app" required />
                <x-admin.form.enum-select name="platform" :label="__('content.fields.platform')" :enum="\App\Enums\Content\AppPlatform::class" :value="$link?->platform" required />
                <x-admin.form.input name="url" type="url" dir="ltr" :label="__('content.fields.store_url')" :hint="__('content.hints.store_url')" :value="$link?->url" maxlength="500" required />
                <x-admin.form.input name="sort_order" type="number" min="0" max="65535" :label="__('content.fields.sort_order')" :value="$link?->sort_order ?? 0" />
                <x-admin.form.toggle name="active" :label="__('content.fields.active')" :hint="__('content.hints.link_active')" :checked="$link?->active ?? true" />
            </x-admin.form.section>
            <x-admin.form.actions :cancel="route('admin.content-app-links.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

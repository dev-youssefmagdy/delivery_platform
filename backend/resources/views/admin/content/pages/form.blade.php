@php
    $kind = $page?->kind ?? $kind;
    $title = $page ? __('content.pages.edit_title') : __('content.pages.create_title');
@endphp
<x-admin.layouts.app :title="$title">
    <x-admin.page-header
        :title="$title"
        :breadcrumbs="array_filter([
            ['label' => __('content.pages.title'), 'url' => route('admin.content-pages.index')],
            $page ? ['label' => $page->title, 'url' => route('admin.content-pages.show', $page)] : null,
            ['label' => $page ? __('admin.common.edit') : __('admin.common.create')],
        ])"
    />

    <x-admin.card>
        <x-admin.form :action="$page ? route('admin.content-pages.update', $page) : route('admin.content-pages.store')" :method="$page ? 'PUT' : 'POST'">
            @if ($page)
                <input type="hidden" name="lock_version" value="{{ $page->lock_version }}">
            @endif
            <div x-data="{ kind: @js($kind->value) }" x-on:change="if ($event.target.name === 'kind') kind = $event.target.value">
                <x-admin.form.section :title="__('content.sections.basics')">
                    @if ($page)
                        <input type="hidden" name="kind" value="{{ $kind->value }}">
                        <x-admin.detail-list><x-admin.detail-list.item :label="__('content.fields.kind')" :value="$kind->label()" /></x-admin.detail-list>
                    @else
                        <x-admin.form.enum-select name="kind" :label="__('content.fields.kind')" :enum="\App\Enums\Content\PageKind::class" :value="$kind" required />
                    @endif
                    <x-admin.form.input name="slug" dir="ltr" :label="__('content.fields.slug')" :hint="__('content.hints.slug_page')" :value="$page?->slug" maxlength="96" required />
                    <x-admin.form.translatable-input name="title" :label="__('content.fields.title')" :value="$page?->getTranslations('title') ?? []" maxlength="191" required />
                    <x-admin.form.translatable-textarea name="summary" :label="__('content.fields.summary')" :value="$page?->getTranslations('summary') ?? []" :rows="3" maxlength="500" />
                    <x-admin.content.media-select name="hero_media_id" :label="__('content.fields.hero_image')" :media="$page?->hero" />
                    <x-admin.content.fallback-toggle :model="$page" />
                </x-admin.form.section>

                <div x-show="kind === 'service'" x-cloak>
                    <x-admin.form.section :title="__('content.sections.service_target')" :description="__('content.sections.service_target_hint')">
                        <x-admin.form.remote-select name="service_category_id" lookup="service-categories" :label="__('content.fields.service_category')" :selected="$page?->serviceCategory ? [$page->serviceCategory->id => $page->serviceCategory->name] : []" />
                    </x-admin.form.section>
                </div>
                <div x-show="kind === 'city'" x-cloak>
                    <x-admin.form.section :title="__('content.sections.city_target')" :description="__('content.sections.city_target_hint')">
                        <x-admin.form.remote-select name="city_id" lookup="cities" :label="__('content.fields.city')" :selected="$page?->city ? [$page->city->id => $page->city->name] : []" />
                    </x-admin.form.section>
                </div>
            </div>

            <x-admin.form.section :title="__('content.sections.content')" :description="__('content.sections.content_hint')">
                <x-admin.content.body-fields :model="$page" />
            </x-admin.form.section>

            <x-admin.form.section :title="__('content.sections.blocks')" :description="__('content.sections.blocks_hint')">
                <x-admin.content.block-editor :blocks="$page?->blocks ?? []" />
            </x-admin.form.section>

            <x-admin.content.seo-fields :model="$page" />

            <x-admin.form.actions :cancel="$page ? route('admin.content-pages.show', $page) : route('admin.content-pages.index')" :submit="__('content.save_draft')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

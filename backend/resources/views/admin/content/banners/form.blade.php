@php($title = $banner ? __('content.banners.edit_title') : __('content.banners.create_title'))
<x-admin.layouts.app :title="$title">
    <x-admin.page-header
        :title="$title"
        :breadcrumbs="array_filter([
            ['label' => __('content.banners.title'), 'url' => route('admin.content-banners.index')],
            $banner ? ['label' => $banner->title, 'url' => route('admin.content-banners.show', $banner)] : null,
            ['label' => $banner ? __('admin.common.edit') : __('admin.common.create')],
        ])"
    />

    <x-admin.card>
        <x-admin.form :action="$banner ? route('admin.content-banners.update', $banner) : route('admin.content-banners.store')" :method="$banner ? 'PUT' : 'POST'">
            @if ($banner)
                <input type="hidden" name="lock_version" value="{{ $banner->lock_version }}">
            @endif
            <div x-data="{ kind: @js($banner?->kind->value ?? 'banner') }" x-on:change="if ($event.target.name === 'kind') kind = $event.target.value">
                <x-admin.form.section :title="__('content.sections.basics')">
                    <x-admin.form.enum-select name="kind" :label="__('content.fields.banner_kind')" :enum="\App\Enums\Content\BannerKind::class" :value="$banner?->kind ?? \App\Enums\Content\BannerKind::Banner" :hint="__('content.hints.banner_kind')" required />
                    <x-admin.form.enum-select name="placement" :label="__('content.fields.placement')" :enum="\App\Enums\Content\BannerPlacement::class" :value="$banner?->placement" :hint="__('content.hints.placement')" required />
                    <x-admin.form.enum-select name="audience" :label="__('content.fields.audience')" :enum="\App\Enums\Content\BannerAudience::class" :value="$banner?->audience ?? \App\Enums\Content\BannerAudience::All" :hint="__('content.hints.audience')" />
                    <x-admin.form.translatable-input name="title" :label="__('content.fields.title')" :value="$banner?->getTranslations('title') ?? []" maxlength="191" required />
                    <x-admin.form.translatable-textarea name="body" :label="__('content.fields.body_plain')" :hint="__('content.hints.body_plain')" :value="$banner?->getTranslations('body') ?? []" :rows="3" maxlength="500" />
                    <div x-show="kind === 'banner'" x-cloak>
                        <x-admin.content.media-select name="media_id" :label="__('content.fields.image')" :media="$banner?->media" />
                    </div>
                    <x-admin.content.fallback-toggle :model="$banner" />
                </x-admin.form.section>

                <x-admin.form.section :title="__('content.sections.link')" :description="__('content.sections.link_hint')">
                    <x-admin.form.translatable-input name="cta_label" :label="__('content.fields.cta_label')" :value="$banner?->getTranslations('cta_label') ?? []" maxlength="60" />
                    <x-admin.form.input name="cta_url" dir="ltr" :label="__('content.fields.cta_url')" :hint="__('content.hints.cta_url')" :value="$banner?->cta_url" maxlength="500" />
                </x-admin.form.section>

                <x-admin.form.section :title="__('content.sections.schedule')" :description="__('content.sections.schedule_hint', ['timezone' => config('admin.display_timezone')])">
                    <x-admin.form.datetime name="starts_at" :label="__('content.fields.starts_at')" :value="$banner?->starts_at" />
                    <x-admin.form.datetime name="ends_at" :label="__('content.fields.ends_at')" :value="$banner?->ends_at" />
                    <x-admin.form.input name="sort_order" type="number" min="0" max="65535" :label="__('content.fields.sort_order')" :hint="__('content.hints.sort_order')" :value="$banner?->sort_order ?? 0" />
                </x-admin.form.section>
            </div>

            <x-admin.form.actions :cancel="$banner ? route('admin.content-banners.show', $banner) : route('admin.content-banners.index')" :submit="__('content.save_draft')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

@props(['model' => null])
{{-- SEO fields of a page or article: title, description, Open Graph, canonical URL and noindex. $model: ?Model --}}
<x-admin.form.section :title="__('content.sections.seo')" :description="__('content.sections.seo_hint')">
    <x-admin.form.translatable-input name="seo_title" :label="__('content.fields.seo_title')" :hint="__('content.hints.seo_title')" :value="$model?->getTranslations('seo_title') ?? []" maxlength="191" />
    <x-admin.form.translatable-textarea name="seo_description" :label="__('content.fields.seo_description')" :hint="__('content.hints.seo_description')" :value="$model?->getTranslations('seo_description') ?? []" :rows="3" maxlength="320" />
    <x-admin.form.translatable-input name="og_title" :label="__('content.fields.og_title')" :value="$model?->getTranslations('og_title') ?? []" maxlength="191" />
    <x-admin.form.translatable-textarea name="og_description" :label="__('content.fields.og_description')" :value="$model?->getTranslations('og_description') ?? []" :rows="2" maxlength="320" />
    <x-admin.content.media-select name="og_image_id" :label="__('content.fields.og_image')" :media="$model?->ogImage" />
    <x-admin.form.input name="canonical_url" type="url" dir="ltr" :label="__('content.fields.canonical_url')" :hint="__('content.hints.canonical_url')" :value="$model?->canonical_url" maxlength="500" />
    <x-admin.form.toggle name="noindex" :label="__('content.fields.noindex')" :hint="__('content.hints.noindex')" :checked="$model?->noindex ?? false" />
</x-admin.form.section>

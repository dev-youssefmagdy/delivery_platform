@props(['model' => null, 'required' => false, 'rows' => 14])
{{-- Format selector and the ar/en body of pages and articles. Both formats are rendered to sanitized HTML on save. --}}
<x-admin.form.enum-select name="body_format" :label="__('content.fields.body_format')" :enum="\App\Enums\Content\BodyFormat::class" :value="$model?->body_format ?? \App\Enums\Content\BodyFormat::Markdown" :hint="__('content.hints.body_format')" required />
<x-admin.form.translatable-textarea name="body" :label="__('content.fields.body')" :hint="__('content.hints.body')" :value="$model?->getTranslations('body') ?? []" :rows="$rows" maxlength="100000" :required="$required" />

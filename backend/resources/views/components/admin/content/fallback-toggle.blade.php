@props(['model' => null])
<x-admin.form.toggle name="fallback_to_ar" :label="__('content.fields.fallback_to_ar')" :hint="__('content.hints.fallback_to_ar')" :checked="$model?->fallback_to_ar ?? true" />

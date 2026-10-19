@props(['for'])
{{-- Error slot filled by ajax-form.js (dot key, e.g. title.ar / items.0.name); also shows server-side errors on full page loads. --}}
<p {{ $attributes->class(['form-error']) }} data-error-for="{{ $for }}" id="err-{{ \App\Support\Admin\FieldName::id($for, null) }}">{{ $errors->first($for) }}</p>

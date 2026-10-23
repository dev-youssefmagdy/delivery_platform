@props(['name', 'label', 'media' => null, 'hint' => null])
{{-- Image picker over the media library (remote select; upload new images under Content > Media). $media: ?ContentMedia --}}
<x-admin.form.remote-select
    :name="$name"
    lookup="content-media"
    :label="$label"
    :hint="$hint ?? __('content.hints.media')"
    :selected="$media ? [$media->id => '#'.$media->id.' · '.$media->original_name] : []"
    {{ $attributes }}
/>
@if ($media)
    <img src="{{ $media->url(320) }}" alt="{{ $media->getTranslation('alt', app()->getLocale(), true) }}" class="-mt-2 mb-4 size-20 rounded-(--radius-control) border border-slate-200 object-cover" loading="lazy">
@endif

<x-admin.layouts.app :title="__('content.media.edit_title')">
    <x-admin.page-header
        :title="$media->original_name"
        :breadcrumbs="[['label' => __('content.media.title'), 'url' => route('admin.content-media.index')], ['label' => $media->original_name]]"
    />

    <x-admin.card>
        <div class="mb-6 flex flex-wrap items-start gap-6">
            <img src="{{ $media->url(960) }}" alt="{{ $media->getTranslation('alt', app()->getLocale(), true) }}" class="max-h-60 rounded-(--radius-control) border border-slate-200 object-contain">
            <x-admin.detail-list class="min-w-60 flex-1">
                <x-admin.detail-list.item :label="__('content.fields.file')" :value="$media->original_name" />
                <x-admin.detail-list.item :label="__('content.fields.dimensions')" :value="$media->width.' × '.$media->height" />
                <x-admin.detail-list.item :label="__('content.fields.size')" :value="number_format($media->size_bytes / 1024, 0).' KB'" />
                <x-admin.detail-list.item :label="__('content.fields.url')"><span dir="ltr" class="break-all">{{ $media->url() }}</span></x-admin.detail-list.item>
            </x-admin.detail-list>
        </div>
        <x-admin.form :action="route('admin.content-media.update', $media)" method="PUT">
            <x-admin.form.translatable-input name="alt" :label="__('content.fields.alt')" :hint="__('content.hints.alt')" :value="$media->getTranslations('alt')" maxlength="191" />
            <x-admin.form.actions :cancel="route('admin.content-media.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

<x-admin.layouts.app :title="__('content.media.upload')">
    <x-admin.page-header
        :title="__('content.media.upload')"
        :breadcrumbs="[['label' => __('content.media.title'), 'url' => route('admin.content-media.index')], ['label' => __('content.media.upload')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.content-media.store')" method="POST">
            <x-admin.form.section :title="__('content.media.upload')" :description="__('content.media.upload_hint', ['size' => config('content.media.max_kb') / 1024, 'max' => config('content.media.max_dimension')])">
                <x-admin.form.file name="file" :label="__('content.fields.file')" accept="image/jpeg,image/png,image/webp" :max-kb="config('content.media.max_kb')" required />
                <x-admin.form.translatable-input name="alt" :label="__('content.fields.alt')" :hint="__('content.hints.alt')" maxlength="191" />
            </x-admin.form.section>
            <x-admin.form.actions :cancel="route('admin.content-media.index')" :submit="__('content.media.upload')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

@php($editing = $version !== null)
<x-admin.layouts.app :title="$editing ? __('system.app_versions.edit') : __('system.app_versions.create')">
    <x-admin.page-header
        :title="$editing ? __('system.app_versions.edit') : __('system.app_versions.create')"
        :breadcrumbs="[['label' => __('system.app_versions.title'), 'url' => route('admin.app-versions.index')], ['label' => $editing ? __('system.app_versions.edit') : __('system.app_versions.create')]]"
    />

    <x-admin.card class="max-w-3xl">
        <x-admin.form :action="$editing ? route('admin.app-versions.update', $version) : route('admin.app-versions.store')" :method="$editing ? 'PUT' : 'POST'">
            @if ($editing)
                <input type="hidden" name="lock_version" value="{{ $version->lock_version }}">
                <x-admin.detail-list :cols="2" class="mb-5">
                    <x-admin.detail-list.item :label="__('system.app_versions.fields.app')"><x-admin.badge :value="$version->app" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('system.app_versions.fields.platform')"><x-admin.badge :value="$version->platform" /></x-admin.detail-list.item>
                </x-admin.detail-list>
            @else
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.form.enum-select name="app" :label="__('system.app_versions.fields.app')" :enum="\App\Enums\System\AppTarget::class" required />
                    <x-admin.form.enum-select name="platform" :label="__('system.app_versions.fields.platform')" :enum="\App\Enums\System\AppPlatform::class" required />
                </div>
            @endif
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-admin.form.input name="min_version" :label="__('system.app_versions.fields.min_version')" :hint="__('system.app_versions.hints.min_version')" :value="$version?->min_version ?? '1.0.0'" dir="ltr" maxlength="14" required />
                <x-admin.form.input name="latest_version" :label="__('system.app_versions.fields.latest_version')" :hint="__('system.app_versions.hints.latest_version')" :value="$version?->latest_version" dir="ltr" maxlength="14" />
            </div>
            <div class="mt-5 space-y-5">
                <x-admin.form.input name="store_url" type="url" :label="__('system.app_versions.fields.store_url')" :hint="__('system.app_versions.hints.store_url')" :value="$version?->store_url" dir="ltr" maxlength="500" placeholder="https://" />
                <x-admin.form.toggle name="force_update" :label="__('system.app_versions.fields.force_update')" :hint="__('system.app_versions.hints.force_update')" :checked="$version?->force_update ?? false" />
            </div>
            <x-admin.form.actions :cancel="route('admin.app-versions.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

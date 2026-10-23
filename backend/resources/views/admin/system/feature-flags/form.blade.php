@php($editing = $flag !== null)
<x-admin.layouts.app :title="$editing ? __('system.flags.edit') : __('system.flags.create')">
    <x-admin.page-header
        :title="$editing ? __('system.flags.edit') : __('system.flags.create')"
        :breadcrumbs="[['label' => __('system.flags.title'), 'url' => route('admin.feature-flags.index')], ['label' => $editing ? $flag->key->label() : __('system.flags.create')]]"
    />

    <x-admin.card class="max-w-3xl">
        <x-admin.form :action="$editing ? route('admin.feature-flags.update', $flag) : route('admin.feature-flags.store')" :method="$editing ? 'PUT' : 'POST'">
            @if ($editing)
                <input type="hidden" name="lock_version" value="{{ $flag->lock_version }}">
                <x-admin.detail-list :cols="1" class="mb-5">
                    <x-admin.detail-list.item :label="__('system.flags.fields.key')">{{ $flag->key->label() }} <code class="text-xs text-slate-500">{{ $flag->key->value }}</code></x-admin.detail-list.item>
                </x-admin.detail-list>
            @else
                <x-admin.form.enum-select name="key" :label="__('system.flags.fields.key')" :enum="\App\Enums\System\FeatureFlagKey::class" required />
            @endif
            <div class="mt-5 space-y-5">
                <x-admin.form.toggle name="enabled" :label="__('system.flags.fields.enabled')" :hint="__('system.flags.hints.enabled')" :checked="$flag?->enabled ?? false" />
                <x-admin.form.enum-select name="apps[]" :label="__('system.flags.fields.apps')" :hint="__('system.flags.hints.apps')" :enum="\App\Enums\System\AppTarget::class" :value="$flag?->apps ?? []" multiple />
                <x-admin.form.remote-select name="city_ids[]" lookup="cities" :label="__('system.flags.fields.cities')" :hint="__('system.flags.hints.cities')" :selected="$selectedCities" multiple />
                <x-admin.form.input name="rollout_percent" type="number" min="0" max="100" step="1" :label="__('system.flags.fields.rollout')" :hint="__('system.flags.hints.rollout')" :value="$flag?->rollout_percent ?? 100" required />
                <x-admin.form.input name="note" :label="__('system.flags.fields.note')" :value="$flag?->note" maxlength="255" />
            </div>
            <x-admin.form.actions :cancel="route('admin.feature-flags.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

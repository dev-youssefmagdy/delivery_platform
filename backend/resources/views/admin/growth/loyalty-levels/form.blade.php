@php
    $editing = $level !== null;
    $currency = $level?->currency ?? (string) app(\App\Services\Settings\SettingsService::class)->string(\App\Enums\Settings\SettingKey::DefaultCurrency);
    $title = $editing ? __('growth.loyalty.edit') : __('growth.loyalty.create');
@endphp
<x-admin.layouts.app :title="$title">
    <x-admin.page-header
        :title="$title"
        :breadcrumbs="[['label' => __('growth.loyalty.title'), 'url' => route('admin.loyalty-levels.index')], ['label' => $title]]"
    />

    <x-admin.card class="max-w-3xl">
        <x-admin.form :action="$editing ? route('admin.loyalty-levels.update', $level) : route('admin.loyalty-levels.store')" :method="$editing ? 'PUT' : 'POST'">
            @if ($editing)
                <input type="hidden" name="lock_version" value="{{ $level->lock_version }}">
            @endif
            <x-admin.grid>
                <x-admin.form.input name="name" :label="__('growth.fields.name')" :value="$level?->name" maxlength="80" required />
                <x-admin.form.input name="name_en" :label="__('growth.fields.name_en')" :value="$level?->name_en" maxlength="80" dir="ltr" />
                <x-admin.form.input name="min_completed_jobs" type="number" min="1" :label="__('growth.loyalty.fields.min_jobs')" :hint="__('growth.loyalty.hints.min_jobs')" :value="$level?->min_completed_jobs" dir="ltr" required />
                <x-admin.form.money name="reward_minor" :label="__('growth.loyalty.fields.reward')" :hint="__('growth.loyalty.hints.reward')" :value="$level?->reward_minor ?? 0" :currency="$currency" required />
                <x-admin.form.input name="credit_valid_days" type="number" min="1" max="365" :label="__('growth.loyalty.fields.valid_days')" :value="$level?->credit_valid_days ?? 30" dir="ltr" required />
                <x-admin.form.input name="sort" type="number" :label="__('growth.loyalty.fields.sort')" :value="$level?->sort ?? 0" dir="ltr" />
            </x-admin.grid>
            <x-admin.form.actions :cancel="route('admin.loyalty-levels.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

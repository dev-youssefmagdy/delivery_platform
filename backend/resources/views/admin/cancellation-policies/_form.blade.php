{{-- Shared create/edit fields. $model: ?\App\Models\CancellationPolicy --}}
@php
    $currencyCode = (string) app(\App\Services\Settings\SettingsService::class)->string(\App\Enums\Settings\SettingKey::DefaultCurrency);
    $currency = app(\App\Support\Money\CurrencyRegistry::class)->get($currencyCode);
    $decimal = static function (int $minor) use ($currency): string {
        if ($currency->exponent === 0) {
            return (string) $minor;
        }
        $digits = str_pad((string) $minor, $currency->exponent + 1, '0', STR_PAD_LEFT);

        return substr($digits, 0, -$currency->exponent).'.'.substr($digits, -$currency->exponent);
    };
    $percent = static fn (int $bp): string => rtrim(rtrim(number_format($bp / 100, 2, '.', ''), '0'), '.');
    $ruleRows = array_map(static fn (array $rule): array => [
        'actor' => $rule['actor'] ?? '',
        'stage' => $rule['stage'] ?? '',
        'fee_type' => $rule['fee_type'] ?? 'none',
        'amount' => ($rule['fee_type'] ?? '') === 'fixed' ? $decimal((int) $rule['value']) : '',
        'percent' => ($rule['fee_type'] ?? '') === 'percent' ? $percent((int) $rule['value']) : '',
        'cap' => isset($rule['cap_minor']) ? $decimal((int) $rule['cap_minor']) : '',
        'recipient' => $rule['recipient'] ?? 'platform',
        'after_seconds' => (int) ($rule['after_seconds'] ?? 0),
        'provider_share' => isset($rule['provider_share_bp']) ? $percent((int) $rule['provider_share_bp']) : '',
    ], $model?->rules ?? []);
    $rows = old('rules', $ruleRows);
    $reasonOptions = static fn (\App\Enums\Common\ActorType $actor): array => collect(\App\Enums\Booking\CancellationReason::forActor($actor))->mapWithKeys(static fn ($reason): array => [$reason->value => $reason->label()])->all();
    $stageOptions = collect(\App\Enums\Booking\CancellationStage::chargeable())->mapWithKeys(static fn ($stage): array => [$stage->value => $stage->label()])->all();
    $actorOptions = [\App\Enums\Common\ActorType::Customer->value => \App\Enums\Common\ActorType::Customer->label(), \App\Enums\Common\ActorType::Provider->value => \App\Enums\Common\ActorType::Provider->label()];
@endphp
<x-admin.form.section :title="__('admin.cancellation_policies.sections.scope')" :description="__('admin.cancellation_policies.sections.scope_hint')">
    <x-admin.grid>
        <x-admin.form.input name="name" :label="__('admin.cancellation_policies.fields.name')" :value="$model?->name" maxlength="120" required />
        @if ($model)
            <x-admin.form.input name="service_display" :label="__('admin.cancellation_policies.fields.service')" :value="$model->serviceCategory?->name ?? __('admin.cancellation_policies.all_services')" disabled />
            <x-admin.form.input name="zone_display" :label="__('admin.cancellation_policies.fields.zone')" :value="$model->serviceZone?->name ?? __('admin.cancellation_policies.all_zones')" disabled />
        @else
            <x-admin.form.remote-select name="service_category_id" :label="__('admin.cancellation_policies.fields.service')" :hint="__('admin.cancellation_policies.hints.service')" lookup="service-categories" />
            <x-admin.form.remote-select name="service_zone_id" :label="__('admin.cancellation_policies.fields.zone')" :hint="__('admin.cancellation_policies.hints.zone')" lookup="zones" />
        @endif
    </x-admin.grid>
</x-admin.form.section>

<x-admin.form.section :title="__('admin.cancellation_policies.sections.period')" :description="__('admin.cancellation_policies.sections.period_hint')">
    <x-admin.grid>
        <x-admin.form.datetime name="effective_from" :label="__('admin.cancellation_policies.fields.effective_from')" :value="$model?->effective_from ?? now()" required />
        <x-admin.form.datetime name="effective_to" :label="__('admin.cancellation_policies.fields.effective_to')" :hint="__('admin.cancellation_policies.hints.effective_to')" :value="$model?->effective_to" />
    </x-admin.grid>
</x-admin.form.section>

<x-admin.form.section :title="__('admin.cancellation_policies.sections.timing')" :description="__('admin.cancellation_policies.sections.timing_hint')">
    <x-admin.grid>
        <x-admin.form.input name="free_grace_seconds" type="number" min="0" max="86400" step="1" suffix="s" :label="__('admin.cancellation_policies.fields.free_grace')" :hint="__('admin.cancellation_policies.hints.free_grace')" :value="$model?->free_grace_seconds ?? 120" dir="ltr" required />
        <x-admin.form.input name="free_wait_seconds" type="number" min="0" max="86400" step="1" suffix="s" :label="__('admin.cancellation_policies.fields.free_wait')" :hint="__('admin.cancellation_policies.hints.free_wait')" :value="$model?->free_wait_seconds ?? 300" dir="ltr" required />
        <x-admin.form.input name="waiting_rate" :label="__('admin.cancellation_policies.fields.waiting_rate').' ('.$currencyCode.')'" :hint="__('admin.cancellation_policies.hints.waiting_rate')" :value="$decimal((int) ($model?->waiting_rate_minor ?? 0))" inputmode="decimal" dir="ltr" maxlength="18" />
        <x-admin.form.input name="provider_no_show_after_seconds" type="number" min="0" max="86400" step="1" suffix="s" :label="__('admin.cancellation_policies.fields.provider_no_show_after')" :hint="__('admin.cancellation_policies.hints.provider_no_show_after')" :value="$model?->provider_no_show_after_seconds ?? 600" dir="ltr" required />
        <x-admin.form.checkbox name="require_no_show_evidence" :label="__('admin.cancellation_policies.fields.require_evidence')" :hint="__('admin.cancellation_policies.hints.require_evidence')" :checked="$model?->require_no_show_evidence ?? true" />
    </x-admin.grid>
</x-admin.form.section>

<x-admin.form.section :title="__('admin.cancellation_policies.sections.rules')" :description="__('admin.cancellation_policies.sections.rules_hint')">
    <x-admin.alert type="info" class="mb-4">{{ __('admin.cancellation_policies.fee_notice') }}</x-admin.alert>
    <x-admin.form.repeater
        name="rules"
        :items="$rows"
        :max="12"
        :add-label="__('admin.cancellation_policies.rules.add')"
        :fields="[
            ['name' => 'actor', 'label' => __('admin.cancellation_policies.rules.actor'), 'type' => 'select', 'options' => $actorOptions, 'required' => true],
            ['name' => 'stage', 'label' => __('admin.cancellation_policies.rules.stage'), 'type' => 'select', 'options' => $stageOptions, 'required' => true],
            ['name' => 'fee_type', 'label' => __('admin.cancellation_policies.rules.fee_type'), 'type' => 'select', 'options' => \App\Enums\Booking\CancellationFeeType::options(), 'required' => true],
            ['name' => 'amount', 'label' => __('admin.cancellation_policies.rules.amount').' ('.$currencyCode.')', 'type' => 'text', 'attributes' => ['inputmode' => 'decimal', 'dir' => 'ltr', 'maxlength' => 18]],
            ['name' => 'percent', 'label' => __('admin.cancellation_policies.rules.percent'), 'type' => 'text', 'attributes' => ['inputmode' => 'decimal', 'dir' => 'ltr', 'maxlength' => 6]],
            ['name' => 'cap', 'label' => __('admin.cancellation_policies.rules.cap').' ('.$currencyCode.')', 'type' => 'text', 'attributes' => ['inputmode' => 'decimal', 'dir' => 'ltr', 'maxlength' => 18]],
            ['name' => 'recipient', 'label' => __('admin.cancellation_policies.rules.recipient'), 'type' => 'select', 'options' => \App\Enums\Booking\CancellationFeeRecipient::options(), 'required' => true],
            ['name' => 'provider_share', 'label' => __('admin.cancellation_policies.rules.provider_share'), 'type' => 'text', 'attributes' => ['inputmode' => 'decimal', 'dir' => 'ltr', 'maxlength' => 6]],
            ['name' => 'after_seconds', 'label' => __('admin.cancellation_policies.rules.after_seconds'), 'type' => 'text', 'attributes' => ['inputmode' => 'numeric', 'dir' => 'ltr', 'maxlength' => 5]],
        ]"
    />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.cancellation_policies.sections.reasons')" :description="__('admin.cancellation_policies.sections.reasons_hint')">
    <x-admin.grid>
        <x-admin.form.select name="customer_reasons[]" multiple :label="__('admin.cancellation_policies.fields.customer_reasons')" :options="$reasonOptions(\App\Enums\Common\ActorType::Customer)" :value="old('customer_reasons', $model?->customer_reasons ?? array_keys($reasonOptions(\App\Enums\Common\ActorType::Customer)))" required />
        <x-admin.form.select name="provider_reasons[]" multiple :label="__('admin.cancellation_policies.fields.provider_reasons')" :options="$reasonOptions(\App\Enums\Common\ActorType::Provider)" :value="old('provider_reasons', $model?->provider_reasons ?? array_keys($reasonOptions(\App\Enums\Common\ActorType::Provider)))" required />
    </x-admin.grid>
</x-admin.form.section>

<x-admin.form.section :title="__('admin.cancellation_policies.sections.notes')">
    <x-admin.form.textarea name="notes" :label="__('admin.cancellation_policies.fields.notes')" :value="$model?->notes" maxlength="2000" />
</x-admin.form.section>

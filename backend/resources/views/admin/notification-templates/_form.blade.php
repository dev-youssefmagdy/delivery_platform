{{-- Shared create/edit fields. $model: ?\App\Models\NotificationTemplate, $event: ?\App\Enums\Notification\NotificationEvent --}}
@php
    $variableHints = collect(\App\Enums\Notification\NotificationEvent::cases())->mapWithKeys(static fn ($case): array => [$case->value => $case->variables()])->all();
    $sampleHints = collect(\App\Enums\Notification\NotificationEvent::cases())->mapWithKeys(static fn ($case): array => [$case->value => $case->sampleVariables()])->all();
    $currentEvent = $model?->event_key ?? old('event_key');
@endphp
<div x-data="{ eventKey: @js($currentEvent), variables: @js($variableHints), samples: @js($sampleHints), channel: @js($model?->channel->value ?? old('channel')) }">
    <x-admin.form.section :title="__('admin.notification_templates.sections.target')" :description="__('admin.notification_templates.sections.target_hint')">
        <x-admin.grid>
            @if ($model)
                <x-admin.form.input name="event_display" :label="__('admin.notification_templates.fields.event')" :value="$model->event()?->label().' ('.$model->event_key.')'" disabled />
                <x-admin.form.input name="channel_display" :label="__('admin.notification_templates.fields.channel')" :value="$model->channel->label()" disabled />
                <x-admin.form.input name="locale_display" :label="__('admin.notification_templates.fields.locale')" :value="\App\Enums\Common\Locale::tryFrom($model->locale)?->label()" disabled />
            @else
                <x-admin.form.select name="event_key" :label="__('admin.notification_templates.fields.event')" :options="\App\Enums\Notification\NotificationEvent::optionsByCategory()" x-model="eventKey" searchable required />
                <x-admin.form.enum-select name="channel" :label="__('admin.notification_templates.fields.channel')" :enum="\App\Enums\Notification\NotificationChannel::class" x-model="channel" required />
                <x-admin.form.enum-select name="locale" :label="__('admin.notification_templates.fields.locale')" :enum="\App\Enums\Common\Locale::class" required />
            @endif
        </x-admin.grid>
    </x-admin.form.section>

    <x-admin.form.section :title="__('admin.notification_templates.sections.content')" :description="__('admin.notification_templates.sections.content_hint')">
        <x-admin.form.input name="title" :label="__('admin.notification_templates.fields.title')" :hint="__('admin.notification_templates.hints.title')" :value="$model?->title" maxlength="160" />
        <x-admin.form.textarea name="body" :label="__('admin.notification_templates.fields.body')" :hint="__('admin.notification_templates.hints.body')" :value="$model?->body" rows="5" maxlength="1000" required />

        <p class="mt-2 text-xs text-slate-500" x-show="eventKey && variables[eventKey]" x-cloak>
            {{ __('admin.notification_templates.variables_hint') }}
            <template x-for="name in (variables[eventKey] || [])" :key="name"><code class="ltr-only mx-1 rounded bg-slate-100 px-1.5 py-0.5" x-text="'{' + name + '}'"></code></template>
            <span x-show="eventKey && (variables[eventKey] || []).length === 0">{{ __('admin.notification_templates.no_variables') }}</span>
        </p>
        <x-admin.form.toggle name="active" :label="__('admin.notification_templates.fields.active')" :hint="__('admin.notification_templates.hints.active')" :checked="$model?->active ?? true" />
    </x-admin.form.section>

</div>

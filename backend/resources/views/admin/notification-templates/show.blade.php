@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
    $locale = \App\Enums\Common\Locale::tryFrom($template->locale);
@endphp
<x-admin.layouts.app :title="__('admin.notification_templates.show_title')">
    <x-admin.page-header
        :title="$event?->label() ?? $template->event_key"
        :subtitle="$template->channel->label().' · '.($locale?->label() ?? $template->locale)"
        :breadcrumbs="[['label' => __('admin.notification_templates.title'), 'url' => route('admin.notification-templates.index')], ['label' => '#'.$template->id]]"
    >
        @can('update', $template)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.notification-templates.edit', $template)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('activate', $template)
            <x-admin.confirm-action :url="route('admin.notification-templates.activate', ['notificationTemplate' => $template, 'from_show' => 1])" :title="__('admin.notification_templates.confirm.activate_title')" :message="__('admin.notification_templates.confirm.activate_message')" :confirm-label="__('admin.catalog.actions.activate')" variant="success" icon="play">{{ __('admin.catalog.actions.activate') }}</x-admin.confirm-action>
        @endcan
        @can('deactivate', $template)
            <x-admin.confirm-action :url="route('admin.notification-templates.deactivate', ['notificationTemplate' => $template, 'from_show' => 1])" :title="__('admin.notification_templates.confirm.deactivate_title')" :message="__('admin.notification_templates.confirm.deactivate_message')" :confirm-label="__('admin.catalog.actions.deactivate')" reason-required icon="pause">{{ __('admin.catalog.actions.deactivate') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @unless ($template->active)
        <x-admin.alert type="warning" class="mb-6">{{ __('admin.notification_templates.inactive_notice') }}</x-admin.alert>
    @endunless

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'preview' => ['label' => __('admin.notification_templates.preview.tab'), 'icon' => 'eye'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.notification_templates.fields.event')"><span>{{ $event?->label() }}</span> <span class="ltr-only text-xs text-slate-500">{{ $template->event_key }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.notification_templates.fields.category')" :value="$event?->category()->label()" />
                    <x-admin.detail-list.item :label="__('admin.notification_templates.fields.audience')" :value="$event?->audience()->label()" />
                    <x-admin.detail-list.item :label="__('admin.notification_templates.fields.channel')" :value="$template->channel->label()" />
                    <x-admin.detail-list.item :label="__('admin.notification_templates.fields.locale')" :value="$locale?->label()" />
                    <x-admin.detail-list.item :label="__('admin.notification_templates.fields.active')"><x-admin.badge :label="$template->active ? __('admin.common.yes') : __('admin.common.no')" :color="$template->active ? 'success' : 'neutral'" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.notification_templates.fields.version')" :value="$template->version" />
                    <x-admin.detail-list.item :label="__('admin.notification_templates.fields.title')" :value="$template->title" />
                    <x-admin.detail-list.item :label="__('admin.notification_templates.fields.body')"><span class="whitespace-pre-line">{{ $template->body }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.notification_templates.fields.variables')">
                        @forelse ($event?->variables() ?? [] as $name)
                            <code class="ltr-only me-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ '{'.$name.'}' }}</code>
                        @empty
                            <span class="text-slate-500">{{ __('admin.notification_templates.no_variables') }}</span>
                        @endforelse
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.notification_templates.fields.updated_by')" :value="$template->updatedBy?->name" />
                    <x-admin.detail-list.item :label="__('admin.common.updated_at')" :value="$when($template->updated_at)" />
                </x-admin.detail-list>
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="preview">
                <p class="mb-4 text-sm text-slate-600">{{ __('admin.notification_templates.preview.intro') }}</p>
                @if ($event)
                    <x-admin.form :action="route('admin.notification-templates.preview')" method="POST" :live-validation="false">
                        <input type="hidden" name="event_key" value="{{ $template->event_key }}">
                        <input type="hidden" name="channel" value="{{ $template->channel->value }}">
                        @if ($event->sampleVariables() !== [])
                            <x-admin.grid>
                                @foreach ($event->sampleVariables() as $name => $sample)
                                    <x-admin.form.input :name="'variables['.$name.']'" :label="'{'.$name.'}'" :value="$sample" maxlength="120" />
                                @endforeach
                            </x-admin.grid>
                        @endif
                        <x-admin.form.actions :submit="__('admin.notification_templates.preview.render')" submit-icon="eye" />
                    </x-admin.form>
                @endif
                <div id="template-preview-result" class="mt-6" aria-live="polite">
                    @include('admin.notification-templates._preview-result', ['rows' => $preview])
                </div>
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="activity" :url="route('admin.notification-templates.activity', $template)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>

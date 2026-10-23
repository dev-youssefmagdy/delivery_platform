<x-admin.layouts.app :title="__('admin.policy_versions.show_title')">
    <x-admin.page-header
        :title="$policy->title"
        :breadcrumbs="[['label' => __('admin.policy_versions.title'), 'url' => route('admin.policy-versions.index')], ['label' => $policy->title]]"
    >
        @can('update', $policy)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.policy-versions.edit', $policy)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('publish', $policy)
            <x-admin.confirm-action
                :url="route('admin.policy-versions.publish', ['policyVersion' => $policy, 'from_show' => 1])"
                :title="__('admin.policy_versions.confirm.publish_title')"
                :message="__('admin.policy_versions.confirm.publish_message')"
                :confirm-label="__('admin.policy_versions.actions.publish')"
                reason-required
                variant="success"
                icon="check-circle"
            >{{ __('admin.policy_versions.actions.publish') }}</x-admin.confirm-action>
        @endcan
        @can('delete', $policy)
            <x-admin.confirm-action :url="route('admin.policy-versions.destroy', ['policyVersion' => $policy, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($policy->isDraft())
        <x-admin.alert type="info" class="mb-6">{{ __('admin.policy_versions.draft_notice') }}</x-admin.alert>
    @else
        <x-admin.alert type="warning" class="mb-6">{{ __('admin.policy_versions.immutable_notice') }}</x-admin.alert>
    @endif

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'content' => ['label' => __('admin.policy_versions.fields.content'), 'icon' => 'clipboard'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.policy_versions.fields.type')" :value="$policy->type->label()" />
                    <x-admin.detail-list.item :label="__('admin.policy_versions.fields.version')" :value="$policy->version === null ? '—' : 'v'.$policy->version" />
                    <x-admin.detail-list.item :label="__('admin.policy_versions.fields.status')"><x-admin.badge :value="$policy->status" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.policy_versions.fields.published_at')" :value="$policy->published_at?->copy()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')" />
                    <x-admin.detail-list.item :label="__('admin.policy_versions.fields.published_by')" :value="$policy->publisher?->name" />
                    <x-admin.detail-list.item :label="__('admin.policy_versions.fields.change_note')" :value="$policy->change_note" />
                    <x-admin.detail-list.item :label="__('admin.common.created_at')" :value="$policy->created_at->copy()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')" />
                </x-admin.detail-list>
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="content">
                <div class="space-y-6">
                    @foreach (\App\Enums\Common\Locale::cases() as $locale)
                        <section lang="{{ $locale->value }}" dir="{{ $locale->direction() }}">
                            <h3 class="mb-1 text-sm font-semibold text-slate-900">{{ $locale->label() }}</h3>
                            @if (filled($policy->getTranslation('content', $locale->value, false)))
                                <p class="mb-2 font-medium text-slate-800">{{ $policy->getTranslation('title', $locale->value, false) }}</p>
                                <div class="whitespace-pre-line rounded-(--radius-control) border border-slate-200 bg-slate-50 p-4 text-sm leading-7 text-slate-700">{{ $policy->getTranslation('content', $locale->value, false) }}</div>
                            @else
                                <p class="text-sm text-slate-500">{{ __('admin.policy_versions.no_translation') }}</p>
                            @endif
                        </section>
                    @endforeach
                </div>
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="activity" :url="route('admin.policy-versions.activity', $policy)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>

@php($canManage = auth('admin')->user()?->can('update', \App\Models\PlatformSetting::class))
<x-admin.layouts.app :title="__('admin.settings.title')">
    <x-admin.page-header
        :title="__('admin.settings.title')"
        :subtitle="__('admin.settings.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.settings.title')]]"
    />

    @unless ($canManage)
        <x-admin.alert type="info" class="mb-6">{{ __('admin.settings.read_only') }}</x-admin.alert>
    @endunless

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :active="$active" :tabs="collect($tabs)->map(fn ($tab) => ['label' => $tab['group']->label(), 'icon' => $tab['group']->icon()])->all()">
            @foreach ($tabs as $id => $tab)
                <x-admin.tabs.panel :id="$id">
                    @if ($canManage)
                        <x-admin.form :action="route('admin.settings.update', $tab['group']->value)" method="PUT">
                            <input type="hidden" name="fingerprint" value="{{ $tab['fingerprint'] }}">
                            <div class="space-y-5">
                                @foreach ($tab['fields'] as $field)
                                    @include('admin.settings._field', ['field' => $field])
                                @endforeach
                            </div>
                            <x-admin.form.actions :submit="__('admin.common.save_changes')" />
                        </x-admin.form>
                    @else
                        <fieldset disabled class="space-y-5">
                            @foreach ($tab['fields'] as $field)
                                @include('admin.settings._field', ['field' => $field])
                            @endforeach
                        </fieldset>
                    @endif
                </x-admin.tabs.panel>
            @endforeach
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>

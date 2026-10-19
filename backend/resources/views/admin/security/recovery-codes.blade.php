<x-admin.layouts.app :title="__('admin.security.recovery_title')">
    <x-admin.page-header
        :title="__('admin.security.recovery_title')"
        :breadcrumbs="[['label' => __('admin.security.title'), 'url' => route('admin.security.index')], ['label' => __('admin.security.recovery_title')]]"
    />

    <x-admin.card class="max-w-2xl">
        <x-admin.alert type="warning" :title="__('admin.security.recovery_warning_title')">{{ __('admin.security.recovery_warning') }}</x-admin.alert>

        <ul class="mt-6 grid grid-cols-2 gap-3" dir="ltr">
            @foreach ($codes as $code)
                <li class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-center font-mono text-base tracking-wider text-slate-900">{{ $code }}</li>
            @endforeach
        </ul>

        <x-slot:footer>
            <x-admin.button :href="route('admin.security.index')" icon="check">{{ __('admin.security.recovery_done') }}</x-admin.button>
        </x-slot:footer>
    </x-admin.card>
</x-admin.layouts.app>

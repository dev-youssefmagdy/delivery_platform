<x-admin.layouts.app :title="__('admin.coverage.title')">
    <x-admin.page-header
        :title="__('admin.coverage.title')"
        :subtitle="__('admin.coverage.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.coverage.title')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.coverage-preview.run')" method="POST">
            <x-admin.grid>
                <x-admin.form.map-point lat-name="origin_lat" lng-name="origin_lng" :label="__('admin.coverage.origin')" required />
                <x-admin.form.map-point lat-name="destination_lat" lng-name="destination_lng" :label="__('admin.coverage.destination')" />
            </x-admin.grid>
            <x-admin.form.actions :submit="__('admin.coverage.run')" />
        </x-admin.form>
    </x-admin.card>

    <div id="coverage-result" class="mt-6" aria-live="polite"></div>
</x-admin.layouts.app>

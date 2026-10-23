@php
    $table = \App\DataTables\Admin\Reviews\ReviewsDataTable::TABLE_ID;
    $tabs = [
        'reviews' => ['label' => __('reputation.admin.tabs.reviews'), 'icon' => 'star'],
        'reports' => ['label' => __('reputation.admin.tabs.reports').($openReports > 0 ? ' ('.$openReports.')' : ''), 'icon' => 'exclamation'],
        'appeals' => ['label' => __('reputation.admin.tabs.appeals').($openAppeals > 0 ? ' ('.$openAppeals.')' : ''), 'icon' => 'clipboard'],
    ];
@endphp
<x-admin.layouts.app :title="__('reputation.admin.reviews.title')">
    <x-admin.page-header
        :title="__('reputation.admin.reviews.title')"
        :subtitle="__('reputation.admin.reviews.subtitle', ['reports' => $openReports, 'appeals' => $openAppeals])"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('reputation.admin.reviews.title')]]"
    />

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="$tabs">
            <x-admin.tabs.panel id="reviews">
                <p class="mb-4 text-sm text-slate-500">{{ __('reputation.admin.reviews.intro') }}</p>
                <x-admin.filters-card for="{{ $table }}">
                    <x-admin.filters.number-range name="stars" :label="__('reputation.admin.fields.stars')" step="1" min="1" />
                    <x-admin.filters.enum name="statuses" :label="__('admin.ratings.fields.moderation')" :enum="\App\Enums\Reviews\ModerationStatus::class" multiple />
                    <x-admin.filters.boolean name="reported_only" :label="__('reputation.admin.fields.reported_only')" />
                    <x-admin.filters.select name="ratee_type" :label="__('reputation.admin.fields.ratee_type')" :options="['customer' => __('reputation.enums.subject.customer'), 'provider' => __('reputation.enums.subject.provider')]" />
                    <x-admin.filters.text name="booking" :label="__('admin.bookings.fields.code')" />
                    <x-admin.filters.date-range name="created" :label="__('admin.common.created_at')" />
                </x-admin.filters-card>
                <x-admin.datatable :table="$dataTable" />
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="reports" :url="route('admin.reviews.tabs.reports')" />
            <x-admin.tabs.panel id="appeals" :url="route('admin.reviews.tabs.appeals')" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>

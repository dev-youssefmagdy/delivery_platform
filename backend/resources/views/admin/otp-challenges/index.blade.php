<x-admin.layouts.app :title="__('admin.otp_challenges.title')">
    <x-admin.page-header
        :title="__('admin.otp_challenges.title')"
        :subtitle="__('admin.otp_challenges.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.otp_challenges.title')]]"
    />

    <x-admin.filters-card for="otp-challenges-table">
        <x-admin.filters.text name="phone" :label="__('admin.otp_challenges.fields.phone_full')" />
        <x-admin.filters.enum name="states" :label="__('admin.otp_challenges.fields.state')" :enum="\App\Enums\Identity\OtpChallengeState::class" multiple />
        <x-admin.filters.enum name="deliveries" :label="__('admin.otp_challenges.fields.delivery')" :enum="\App\Enums\Identity\OtpDeliveryStatus::class" multiple />
        <x-admin.filters.enum name="apps" :label="__('admin.otp_challenges.fields.app')" :enum="\App\Enums\Identity\ClientApp::class" multiple />
        <x-admin.filters.text name="ip" :label="__('admin.otp_challenges.fields.ip')" />
        <x-admin.filters.date-range name="created_at" :label="__('admin.otp_challenges.fields.time')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>

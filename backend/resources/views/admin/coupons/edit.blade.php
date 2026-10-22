<x-admin.layouts.app :title="__('admin.coupons.edit_title')">
    <x-admin.page-header
        :title="__('admin.coupons.edit_title')"
        :breadcrumbs="[['label' => __('admin.coupons.title'), 'url' => route('admin.coupons.index')], ['label' => $coupon->name, 'url' => route('admin.coupons.show', $coupon)], ['label' => __('admin.common.edit')]]"
    />

    @if ($coupon->status->isLive())
        <x-admin.alert type="warning" class="mb-6">{{ __('admin.coupons.edit_live_notice') }}</x-admin.alert>
    @endif

    <x-admin.card>
        <x-admin.form :action="route('admin.coupons.update', $coupon)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $coupon->lock_version }}">
            @include('admin.coupons._form', ['model' => $coupon])
            <x-admin.form.actions :cancel="route('admin.coupons.show', $coupon)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>

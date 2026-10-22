@php
    $definitions = app(\App\Services\Coupon\CouponDefinitionFactory::class);
    $terms = $definitions->termsOf($coupon);
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
    $conditions = $presenter->conditions($coupon);
    $canManage = auth('admin')->user()?->can(\App\Enums\Admin\AdminPermission::CouponsManage->value);
@endphp
<x-admin.layouts.app :title="__('admin.coupons.show_title')">
    <x-admin.page-header
        :title="$coupon->name"
        :subtitle="$coupon->code ?? __('admin.coupons.automatic')"
        :breadcrumbs="[['label' => __('admin.coupons.title'), 'url' => route('admin.coupons.index')], ['label' => $coupon->code ?? '#'.$coupon->id]]"
    >
        @can('update', $coupon)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.coupons.edit', $coupon)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('publish', $coupon)
            <x-admin.confirm-action :url="route('admin.coupons.publish', ['coupon' => $coupon, 'from_show' => 1])" :title="__('admin.coupons.confirm.publish_title')" :message="__('admin.coupons.confirm.publish_message')" :confirm-label="__('admin.coupons.actions.publish')" variant="success" icon="play">{{ __('admin.coupons.actions.publish') }}</x-admin.confirm-action>
        @endcan
        @can('resume', $coupon)
            <x-admin.confirm-action :url="route('admin.coupons.resume', ['coupon' => $coupon, 'from_show' => 1])" :title="__('admin.coupons.confirm.resume_title')" :message="__('admin.coupons.confirm.resume_message')" :confirm-label="__('admin.coupons.actions.resume')" variant="success" icon="play">{{ __('admin.coupons.actions.resume') }}</x-admin.confirm-action>
        @endcan
        @can('pause', $coupon)
            <x-admin.confirm-action :url="route('admin.coupons.pause', ['coupon' => $coupon, 'from_show' => 1])" :title="__('admin.coupons.confirm.pause_title')" :message="__('admin.coupons.confirm.pause_message')" :confirm-label="__('admin.coupons.actions.pause')" reason-required icon="pause">{{ __('admin.coupons.actions.pause') }}</x-admin.confirm-action>
        @endcan
        @can('archive', $coupon)
            <x-admin.confirm-action :url="route('admin.coupons.archive', ['coupon' => $coupon, 'from_show' => 1])" :title="__('admin.coupons.confirm.archive_title')" :message="__('admin.coupons.confirm.archive_message')" :confirm-label="__('admin.coupons.actions.archive')" reason-required icon="archive">{{ __('admin.coupons.actions.archive') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'versions' => ['label' => __('admin.coupons.tabs.versions'), 'icon' => 'clock'],
            'redemptions' => ['label' => __('admin.coupons.tabs.redemptions'), 'icon' => 'tag'],
            'attempts' => ['label' => __('admin.coupons.tabs.attempts'), 'icon' => 'clipboard'],
            'performance' => ['label' => __('admin.coupons.tabs.performance'), 'icon' => 'chart'],
            'test' => ['label' => __('admin.coupons.tabs.test'), 'icon' => 'shield'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'shield'],
        ]">
            <x-admin.tabs.panel id="details">
                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <x-admin.detail-list>
                            <x-admin.detail-list.item :label="__('admin.coupons.fields.status')"><x-admin.badge :value="$coupon->status" /></x-admin.detail-list.item>
                            <x-admin.detail-list.item :label="__('admin.coupons.fields.code')" :value="$coupon->code ?? __('admin.coupons.automatic')" />
                            <x-admin.detail-list.item :label="__('admin.coupons.fields.discount')" :value="$presenter->discount($terms, $coupon->currency)" />
                            <x-admin.detail-list.item :label="__('admin.coupons.fields.funding_source')" :value="$presenter->funding($terms)" />
                            <x-admin.detail-list.item :label="__('admin.coupons.fields.restore_on_refund')" :value="$coupon->restore_on_refund ? __('admin.common.yes') : __('admin.common.no')" />
                            <x-admin.detail-list.item :label="__('admin.coupons.fields.stackable')" :value="$coupon->stackable ? __('admin.common.yes') : __('admin.common.no')" />
                            <x-admin.detail-list.item :label="__('admin.coupons.fields.usage')" :value="$coupon->used_count.' / '.($coupon->global_limit ?? '∞').' ('.__('admin.coupons.performance.reserved_now', ['count' => $coupon->reserved_count]).')'" />
                            <x-admin.detail-list.item :label="__('admin.coupons.fields.budget')" :value="$coupon->budget_minor === null ? null : $presenter->money($coupon->spent_minor, $coupon->currency).' / '.$presenter->money($coupon->budget_minor, $coupon->currency)" />
                            <x-admin.detail-list.item :label="__('admin.coupons.fields.per_customer_limit')" :value="$coupon->per_customer_limit" />
                            <x-admin.detail-list.item :label="__('admin.coupons.fields.starts_at')" :value="$when($coupon->starts_at)" />
                            <x-admin.detail-list.item :label="__('admin.coupons.fields.ends_at')" :value="$when($coupon->ends_at) ?? __('admin.coupons.open_ended')" />
                            <x-admin.detail-list.item :label="__('admin.coupons.fields.timezone')" :value="$coupon->timezone" />
                            <x-admin.detail-list.item :label="__('admin.coupons.fields.version')" :value="$coupon->version" />
                            <x-admin.detail-list.item :label="__('admin.coupons.fields.priority')" :value="$coupon->priority" />
                        </x-admin.detail-list>
                    </div>

                    <aside class="rounded-(--radius-control) border border-slate-200 bg-slate-50 p-4" aria-label="{{ __('admin.coupons.preview.title') }}">
                        <h3 class="text-sm font-semibold text-slate-800">{{ __('admin.coupons.preview.title') }}</h3>
                        <p class="mt-1 text-xs text-slate-500">{{ __('admin.coupons.preview.hint') }}</p>
                        <p class="mt-3 text-base font-semibold text-slate-900">{{ $coupon->getTranslation('title', app()->getLocale(), true) }}</p>
                        <p class="mt-1 text-sm text-brand-700">{{ $presenter->discount($terms, $coupon->currency) }}</p>
                        @if ($coupon->description)
                            <p class="mt-2 text-sm text-slate-600">{{ $coupon->getTranslation('description', app()->getLocale(), true) }}</p>
                        @endif
                        @if ($conditions !== [])
                            <ul class="mt-3 list-disc space-y-1 ps-5 text-sm text-slate-600">
                                @foreach ($conditions as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($coupon->legal_terms)
                            <p class="mt-3 whitespace-pre-line text-xs text-slate-500">{{ $coupon->getTranslation('legal_terms', app()->getLocale(), true) }}</p>
                        @endif
                    </aside>
                </div>
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="versions">
                <p class="mb-4 text-sm text-slate-500">{{ __('admin.coupons.versions_intro') }}</p>
                <ul class="divide-y divide-slate-100 rounded-(--radius-control) border border-slate-200 text-sm">
                    @foreach ($coupon->versions as $row)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5">
                            <span class="font-medium">{{ __('admin.coupons.version_n', ['n' => $row->version]) }}@if ($row->version === $coupon->version) <span class="ms-2 text-xs text-success-700">{{ __('admin.coupons.current') }}</span>@endif</span>
                            <span class="text-slate-700">{{ $presenter->discount($row->termsObject(), $coupon->currency) }} · {{ $presenter->funding($row->termsObject()) }}</span>
                            <span class="text-slate-500">{{ $when($row->created_at) }}@if ($row->reason) · {{ $row->reason }}@endif</span>
                        </li>
                    @endforeach
                </ul>
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="redemptions" :url="route('admin.coupons.tabs.redemptions', $coupon)" />
            <x-admin.tabs.panel id="attempts" :url="route('admin.coupons.tabs.attempts', $coupon)" />
            <x-admin.tabs.panel id="performance" :url="route('admin.coupons.tabs.performance', $coupon)" />

            <x-admin.tabs.panel id="test">
                <p class="mb-4 text-sm text-slate-600">{{ __('admin.coupons.test.intro') }}</p>
                <x-admin.form :action="route('admin.coupons.test', $coupon)" method="POST">
                    <x-admin.grid>
                        <x-admin.form.remote-select name="customer_id" :label="__('admin.coupons.test.customer')" lookup="customers" required />
                        <x-admin.form.remote-select name="service_category_id" :label="__('admin.coupons.test.service')" lookup="service-categories" required />
                        <x-admin.form.money name="fare_minor" :label="__('admin.coupons.test.fare')" :value="10000" :currency="$coupon->currency" required />
                        <x-admin.form.enum-select name="payment_method" :label="__('admin.coupons.test.payment_method')" :enum="\App\Enums\Payments\PaymentMethodType::class" />
                        <x-admin.form.remote-select name="city_id" :label="__('admin.coupons.test.city')" lookup="cities" />
                        <x-admin.form.remote-select name="zone_id" :label="__('admin.coupons.test.zone')" lookup="zones" />
                        <x-admin.form.datetime name="at" :label="__('admin.coupons.test.at')" :hint="__('admin.coupons.test.at_hint')" />
                    </x-admin.grid>
                    <x-admin.form.actions :submit="__('admin.coupons.test.run')" />
                </x-admin.form>
                <div id="coupon-test-result" class="mt-6" aria-live="polite"></div>
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="activity" :url="route('admin.coupons.activity', $coupon)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>

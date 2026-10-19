<x-admin.layouts.app :title="__('admin.security.mfa_setup')">
    <x-admin.page-header
        :title="__('admin.security.mfa_setup')"
        :subtitle="__('admin.security.mfa_setup_subtitle')"
        :breadcrumbs="[['label' => __('admin.security.title'), 'url' => route('admin.security.index')], ['label' => __('admin.security.mfa_setup')]]"
    />

    <x-admin.card class="max-w-3xl">
        <ol class="space-y-8">
            <li>
                <h3 class="text-sm font-semibold text-slate-900">{{ __('admin.security.mfa_step_scan') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('admin.security.mfa_step_scan_hint') }}</p>
                <div class="mt-4 flex flex-wrap items-center gap-6">
                    <div class="rounded-lg border border-slate-200 bg-white p-2" role="img" aria-label="{{ __('admin.security.qr_label') }}">{!! $enrolment->qrSvg !!}</div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">{{ __('admin.security.manual_key') }}</p>
                        <p class="ltr-only mt-1 font-mono text-base tracking-wider break-all text-slate-900" dir="ltr">{{ $enrolment->formattedSecret() }}</p>
                    </div>
                </div>
            </li>
            <li>
                <h3 class="text-sm font-semibold text-slate-900">{{ __('admin.security.mfa_step_confirm') }}</h3>
                <x-admin.form :action="route('admin.security.mfa.confirm')" :live-validation="false" class="mt-3 max-w-sm">
                    <x-admin.form.input name="code" :label="__('admin.auth.fields.code')" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required dir="ltr" />
                    <x-admin.form.actions :cancel="route('admin.security.index')" :submit="__('admin.security.mfa_enable')" />
                </x-admin.form>
            </li>
        </ol>
    </x-admin.card>
</x-admin.layouts.app>

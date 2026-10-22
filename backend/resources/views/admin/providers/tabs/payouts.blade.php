{{-- Payouts tab: the provider's payout methods (masked) with their verification, and the payout requests. --}}
<p class="mb-4 text-sm text-slate-500">{{ __('admin.providers.payouts.intro') }}</p>

<h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.providers.payouts.methods') }}</h3>
@if ($methods->isEmpty())
    <x-admin.empty-state icon="banknotes" :title="__('admin.providers.payouts.no_methods')" compact />
@else
    <div class="mb-8 overflow-x-auto rounded-(--radius-card) border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-xs font-semibold text-slate-600">
                <tr>
                    <th class="px-4 py-2 text-start">{{ __('admin.providers.payouts.method_label') }}</th>
                    <th class="px-4 py-2 text-start">{{ __('admin.providers.payouts.method_type') }}</th>
                    <th class="px-4 py-2 text-start">{{ __('admin.providers.payouts.method_last4') }}</th>
                    <th class="px-4 py-2 text-start">{{ __('admin.providers.payouts.method_status') }}</th>
                    <th class="px-4 py-2 text-end">{{ __('admin.common.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($methods as $method)
                    <tr>
                        <td class="px-4 py-2">{{ $method->label }}@if ($method->is_default) <x-admin.badge :label="__('admin.providers.payouts.default')" color="info" />@endif</td>
                        <td class="px-4 py-2">{{ $method->type->label() }}</td>
                        <td class="px-4 py-2 tabular-nums" dir="ltr">{{ $method->last4 ? '•••• '.$method->last4 : '—' }}</td>
                        <td class="px-4 py-2"><x-admin.badge :label="$method->isVerified() ? __('admin.providers.payouts.verified') : __('admin.providers.payouts.unverified')" :color="$method->isVerified() ? 'success' : 'warning'" /></td>
                        <td class="px-4 py-2 text-end">
                            @can('verify', $method)
                                <x-admin.confirm-action :url="route('admin.payout-methods.verify', $method)" :title="__('admin.payout_methods.confirm.verify_title')" :message="__('admin.payout_methods.confirm.verify_message')" :confirm-label="__('admin.payout_methods.actions.verify')" reason-required variant="success" icon="check-circle">{{ __('admin.payout_methods.actions.verify') }}</x-admin.confirm-action>
                            @endcan
                            @can('unverify', $method)
                                <x-admin.confirm-action :url="route('admin.payout-methods.unverify', $method)" :title="__('admin.payout_methods.confirm.unverify_title')" :message="__('admin.payout_methods.confirm.unverify_message')" :confirm-label="__('admin.payout_methods.actions.unverify')" reason-required icon="x-mark">{{ __('admin.payout_methods.actions.unverify') }}</x-admin.confirm-action>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.providers.payouts.requests') }}</h3>
<x-admin.datatable :table="$dataTable" />

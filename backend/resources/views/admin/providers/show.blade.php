@php
    /** @var \App\DTOs\Provider\ProviderDetails $details */
    $provider = $details->profile;
    $user = $details->user;
    $name = \App\DataTables\Admin\Provider\ProvidersDataTable::displayName($provider);
    $tz = (string) config('admin.display_timezone');
    $format = static fn (?\Carbon\CarbonInterface $date): ?string => $date?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
    $status = $provider->status;
@endphp
<x-admin.layouts.app :title="__('admin.providers.show_title')">
    <x-admin.page-header
        :title="$name"
        :subtitle="__('admin.providers.show_subtitle', ['uuid' => $provider->uuid])"
        :breadcrumbs="[['label' => __('admin.providers.title'), 'url' => route('admin.providers.index')], ['label' => $name]]"
    >
        @if ($status->isAwaitingReview())
            @can('view', $provider)
                <x-admin.button variant="secondary" icon="clipboard" :href="route('admin.verification.review', $provider)">{{ __('admin.verification.actions.review') }}</x-admin.button>
            @endcan
        @endif
        @can('suspend', $provider)
            <x-admin.button variant="danger" icon="pause" data-modal-open="provider-suspend">{{ __('admin.providers.actions.suspend') }}</x-admin.button>
        @endcan
        @can('reactivate', $provider)
            <x-admin.confirm-action :url="route('admin.providers.reactivate', ['provider' => $provider, 'from_show' => 1])" :title="__('admin.providers.confirm.reactivate_title')" :message="__('admin.providers.confirm.reactivate_message')" :confirm-label="__('admin.providers.actions.reactivate')" variant="success" reason-required icon="play">{{ __('admin.providers.actions.reactivate') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($user->isAnonymised())
        <x-admin.alert type="info" class="mb-6">{{ __('admin.providers.notices.anonymised', ['date' => $format($user->anonymised_at)]) }}</x-admin.alert>
    @elseif ($status === \App\Enums\Provider\ProviderStatus::TemporarilySuspended)
        <x-admin.alert type="danger" class="mb-6" :title="__('admin.providers.notices.suspended')">
            {{ $provider->status_expires_at ? __('admin.providers.notices.suspended_until', ['date' => $format($provider->status_expires_at)]) : __('admin.providers.notices.suspended_open') }}
            @if ($provider->status_reason)
                <span class="block">{{ __('admin.providers.notices.reason', ['reason' => $provider->status_reason]) }}</span>
            @endif
        </x-admin.alert>
    @elseif ($status === \App\Enums\Provider\ProviderStatus::PermanentlySuspended)
        <x-admin.alert type="danger" class="mb-6" :title="__('admin.providers.notices.permanently_suspended')">
            {{ __('admin.providers.notices.permanent_hint') }}
            @if ($provider->status_reason)
                <span class="block">{{ __('admin.providers.notices.reason', ['reason' => $provider->status_reason]) }}</span>
            @endif
        </x-admin.alert>
    @elseif ($status->isAwaitingReview())
        <x-admin.alert type="info" class="mb-6" :title="$status->label()">{{ __('admin.providers.notices.in_review', ['date' => $format($provider->submitted_at)]) }}</x-admin.alert>
    @elseif ($status === \App\Enums\Provider\ProviderStatus::Rejected)
        <x-admin.alert type="danger" class="mb-6" :title="__('admin.providers.notices.rejected')">
            @if ($provider->status_reason)
                {{ __('admin.providers.notices.reason', ['reason' => $provider->status_reason]) }}
            @endif
        </x-admin.alert>
    @elseif ($status === \App\Enums\Provider\ProviderStatus::DeletionPending)
        <x-admin.alert type="warning" class="mb-6" :title="__('admin.providers.notices.deletion_pending')">
            {{ __('admin.providers.notices.deletion_pending_hint') }}
            @if ($details->pendingDeletionRequestUuid)
                @can('processDeletion', $provider)
                    <span class="mt-3 flex flex-wrap gap-2">
                        <x-admin.confirm-action :url="route('admin.providers.deletion-requests.approve', ['provider' => $provider, 'deletionRequest' => $details->pendingDeletionRequestUuid])" :title="__('admin.providers.confirm.approve_deletion_title')" :message="__('admin.providers.confirm.approve_deletion_message')" :confirm-label="__('admin.providers.actions.approve_deletion')" reason-required icon="check-circle">{{ __('admin.providers.actions.approve_deletion') }}</x-admin.confirm-action>
                        <x-admin.confirm-action :url="route('admin.providers.deletion-requests.reject', ['provider' => $provider, 'deletionRequest' => $details->pendingDeletionRequestUuid])" :title="__('admin.providers.confirm.reject_deletion_title')" :message="__('admin.providers.confirm.reject_deletion_message')" :confirm-label="__('admin.providers.actions.reject_deletion')" variant="secondary" reason-required icon="x-mark">{{ __('admin.providers.actions.reject_deletion') }}</x-admin.confirm-action>
                    </span>
                @endcan
            @endif
        </x-admin.alert>
    @endif

    @php
        $showPayouts = \Illuminate\Support\Facades\Gate::allows('viewAny', \App\Models\PayoutRequest::class);
        $showWallet = \Illuminate\Support\Facades\Gate::allows('viewWallets', \App\Models\LedgerAccount::class);
        $showRisk = \Illuminate\Support\Facades\Gate::allows('viewAny', \App\Models\RiskSignal::class);
        $tabs = [
            'profile' => ['label' => __('admin.providers.tabs.profile'), 'icon' => 'user'],
            'services' => ['label' => __('admin.providers.tabs.services'), 'icon' => 'squares'],
            'vehicles' => ['label' => __('admin.providers.tabs.vehicles'), 'icon' => 'cube'],
            'documents' => ['label' => __('admin.providers.tabs.documents'), 'icon' => 'document'],
            'history' => ['label' => __('admin.providers.tabs.history'), 'icon' => 'clock'],
            'jobs' => ['label' => __('admin.providers.tabs.jobs'), 'icon' => 'clipboard'],
            'earnings' => ['label' => __('admin.providers.tabs.earnings'), 'icon' => 'banknotes'],
            ...($showPayouts ? ['payouts' => ['label' => __('admin.providers.tabs.payouts'), 'icon' => 'banknotes']] : []),
            ...($showWallet ? ['wallet' => ['label' => __('admin.providers.tabs.wallet'), 'icon' => 'banknotes']] : []),
            'ratings' => ['label' => __('admin.providers.tabs.ratings'), 'icon' => 'star'],
            ...($showRisk ? ['risk' => ['label' => __('reputation.admin.tabs.risk'), 'icon' => 'shield']] : []),
            'data-requests' => ['label' => __('admin.providers.tabs.data_requests'), 'icon' => 'archive'],
            'audit' => ['label' => __('admin.providers.tabs.audit'), 'icon' => 'shield'],
        ];
    @endphp

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="$tabs">
            <x-admin.tabs.panel id="profile">
                @if ($details->canRevealContact)
                    <x-admin.reveal-fields :url="route('admin.providers.reveal', $provider)" :label="__('admin.providers.reveal.button')">
                        @include('admin.providers._profile')
                    </x-admin.reveal-fields>
                @else
                    @include('admin.providers._profile')
                @endif
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="services" :url="route('admin.providers.tabs.services', $provider)" />
            <x-admin.tabs.panel id="vehicles" :url="route('admin.providers.tabs.vehicles', $provider)" />
            <x-admin.tabs.panel id="documents" :url="route('admin.providers.tabs.documents', $provider)" />
            <x-admin.tabs.panel id="history" :url="route('admin.providers.tabs.history', $provider)" />
            <x-admin.tabs.panel id="jobs" :url="route('admin.providers.tabs.jobs', $provider)" />
            <x-admin.tabs.panel id="earnings" :url="route('admin.providers.tabs.earnings', $provider)" />
            @if ($showPayouts)
                <x-admin.tabs.panel id="payouts" :url="route('admin.providers.tabs.payouts', $provider)" />
            @endif
            @if ($showWallet)
                <x-admin.tabs.panel id="wallet" :url="route('admin.providers.tabs.wallet', $provider)" />
            @endif
            <x-admin.tabs.panel id="ratings" :url="route('admin.providers.tabs.ratings', $provider)" />
            @if ($showRisk)
                <x-admin.tabs.panel id="risk" :url="route('admin.providers.tabs.risk', $provider)" />
            @endif
            <x-admin.tabs.panel id="data-requests" :url="route('admin.providers.tabs.deletion-requests', $provider)" />
            <x-admin.tabs.panel id="audit" :url="route('admin.providers.tabs.audit', $provider)" />
        </x-admin.tabs>
    </x-admin.card>

    @can('suspend', $provider)
        <x-admin.action-modal id="provider-suspend" :title="__('admin.providers.suspend_form.title')" :message="__('admin.providers.suspend_form.message')" :action="route('admin.providers.suspend', ['provider' => $provider, 'from_show' => 1])" :submit="__('admin.providers.actions.suspend')" icon="pause">
            <x-admin.form.checkbox name="permanent" id="provider-suspend-permanent" :label="__('admin.providers.fields.permanent')" :hint="__('admin.providers.suspend_form.permanent_hint')" />
            <x-admin.form.datetime name="expires_at" id="provider-suspend-expires" :label="__('admin.providers.fields.suspension_ends')" :hint="__('admin.providers.suspend_form.expiry_hint')" :min="now()->setTimezone($tz)->format('Y-m-d H:i')" />
        </x-admin.action-modal>
    @endcan
</x-admin.layouts.app>

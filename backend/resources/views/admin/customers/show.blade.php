@php
    /** @var \App\DTOs\Customers\CustomerDetails $details */
    $customer = $details->user;
    $profile = $details->profile;
    $name = \App\DataTables\Admin\Customers\CustomersDataTable::displayName($customer);
    $tz = (string) config('admin.display_timezone');
    $format = static fn (?\Carbon\CarbonInterface $date): ?string => $date?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
    $pending = $details->pendingDeletion;
@endphp
<x-admin.layouts.app :title="__('admin.customers.show_title')">
    <x-admin.page-header
        :title="$name"
        :subtitle="__('admin.customers.show_subtitle', ['uuid' => $customer->uuid])"
        :breadcrumbs="[['label' => __('admin.customers.title'), 'url' => route('admin.customers.index')], ['label' => $name]]"
    >
        @can('sendMessage', $customer)
            <x-admin.button variant="secondary" icon="inbox" data-modal-open="customer-send-message">{{ __('admin.customers.actions.send_message') }}</x-admin.button>
        @endcan
        @can('exportData', $customer)
            <x-admin.button variant="secondary" icon="download" :data-export-url="route('admin.customers.export-data', $customer)" data-export-table="customer-personal-data">{{ __('admin.customers.actions.export_data') }}</x-admin.button>
        @endcan
        @can('restrict', $customer)
            <x-admin.button variant="warning" icon="lock-closed" data-modal-open="customer-restrict">{{ __('admin.customers.actions.restrict') }}</x-admin.button>
        @endcan
        @can('suspend', $customer)
            <x-admin.button variant="danger" icon="pause" data-modal-open="customer-suspend">{{ __('admin.customers.actions.suspend') }}</x-admin.button>
        @endcan
        @can('reactivate', $customer)
            <x-admin.confirm-action :url="route('admin.customers.reactivate', ['customer' => $customer, 'from_show' => 1])" :title="__('admin.customers.confirm.reactivate_title')" :message="__('admin.customers.confirm.reactivate_message')" :confirm-label="__('admin.customers.actions.reactivate')" variant="success" reason-required icon="play">{{ __('admin.customers.actions.reactivate') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($customer->isAnonymised())
        <x-admin.alert type="info" class="mb-6">{{ __('admin.customers.notices.anonymised', ['date' => $format($customer->anonymised_at)]) }}</x-admin.alert>
    @elseif ($customer->status === \App\Enums\Identity\UserStatus::Suspended)
        <x-admin.alert type="danger" class="mb-6" :title="__('admin.customers.notices.suspended')">
            {{ $customer->status_expires_at ? __('admin.customers.notices.suspended_until', ['date' => $format($customer->status_expires_at)]) : __('admin.customers.notices.suspended_open') }}
            @if ($customer->status_reason)
                <span class="block">{{ __('admin.customers.notices.reason', ['reason' => $customer->status_reason]) }}</span>
            @endif
        </x-admin.alert>
    @elseif ($customer->status === \App\Enums\Identity\UserStatus::Restricted)
        <x-admin.alert type="warning" class="mb-6" :title="__('admin.customers.notices.restricted')">
            {{ __('admin.customers.notices.restricted_hint') }}
            @if ($customer->status_reason)
                <span class="block">{{ __('admin.customers.notices.reason', ['reason' => $customer->status_reason]) }}</span>
            @endif
        </x-admin.alert>
    @endif

    @if ($pending)
        <x-admin.alert type="warning" class="mb-6" :title="__('admin.customers.notices.deletion_pending')">
            {{ __('admin.customers.notices.deletion_pending_hint', ['date' => $format($pending->requested_at)]) }}
            @if ($pending->reason)
                <span class="block">{{ __('admin.customers.notices.reason', ['reason' => $pending->reason]) }}</span>
            @endif
            @can('processDeletion', $customer)
                <span class="mt-3 flex flex-wrap gap-2">
                    <x-admin.confirm-action :url="route('admin.customers.deletion-requests.approve', ['customer' => $customer, 'deletionRequest' => $pending->uuid])" :title="__('admin.customers.confirm.approve_deletion_title')" :message="__('admin.customers.confirm.approve_deletion_message')" :confirm-label="__('admin.customers.actions.approve_deletion')" reason-required icon="check-circle">{{ __('admin.customers.actions.approve_deletion') }}</x-admin.confirm-action>
                    <x-admin.confirm-action :url="route('admin.customers.deletion-requests.reject', ['customer' => $customer, 'deletionRequest' => $pending->uuid])" :title="__('admin.customers.confirm.reject_deletion_title')" :message="__('admin.customers.confirm.reject_deletion_message')" :confirm-label="__('admin.customers.actions.reject_deletion')" variant="secondary" reason-required icon="x-mark">{{ __('admin.customers.actions.reject_deletion') }}</x-admin.confirm-action>
                </span>
            @endcan
        </x-admin.alert>
    @endif

    @php
        $tabs = [
            'profile' => ['label' => __('admin.customers.tabs.profile'), 'icon' => 'user'],
            'bookings' => ['label' => __('admin.customers.tabs.bookings'), 'icon' => 'clipboard'],
            'payments' => ['label' => __('admin.customers.tabs.payments'), 'icon' => 'banknotes'],
            'coupons' => ['label' => __('admin.customers.tabs.coupons'), 'icon' => 'tag'],
            'tickets' => ['label' => __('admin.customers.tabs.tickets'), 'icon' => 'lifebuoy'],
            'ratings' => ['label' => __('admin.customers.tabs.ratings'), 'icon' => 'star'],
            'consents' => ['label' => __('admin.customers.tabs.consents'), 'icon' => 'document'],
            'devices' => ['label' => __('admin.customers.tabs.devices'), 'icon' => 'window'],
        ];
        if ($details->canRevealContact) {
            $tabs['places'] = ['label' => __('admin.customers.tabs.places'), 'icon' => 'map-pin'];
        }
        $tabs += [
            'data-requests' => ['label' => __('admin.customers.tabs.data_requests'), 'icon' => 'archive'],
            'messages' => ['label' => __('admin.customers.tabs.messages'), 'icon' => 'inbox'],
            'audit' => ['label' => __('admin.customers.tabs.audit'), 'icon' => 'shield'],
        ];
    @endphp

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="$tabs">
            <x-admin.tabs.panel id="profile">
                @if ($details->canRevealContact)
                    <x-admin.reveal-fields :url="route('admin.customers.reveal', $customer)">
                        @include('admin.customers._profile')
                    </x-admin.reveal-fields>
                @else
                    @include('admin.customers._profile')
                @endif
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="bookings" :url="route('admin.customers.tabs.bookings', $customer)" />
            <x-admin.tabs.panel id="payments"><x-admin.available-after :title="__('admin.customers.tabs.payments')" milestone="P3.2" :module="__('admin.customers.modules.payments')" /></x-admin.tabs.panel>
            <x-admin.tabs.panel id="coupons"><x-admin.available-after :title="__('admin.customers.tabs.coupons')" milestone="P3.3" :module="__('admin.customers.modules.coupons')" /></x-admin.tabs.panel>
            <x-admin.tabs.panel id="tickets"><x-admin.available-after :title="__('admin.customers.tabs.tickets')" milestone="P3.7" :module="__('admin.customers.modules.tickets')" /></x-admin.tabs.panel>
            <x-admin.tabs.panel id="ratings"><x-admin.available-after :title="__('admin.customers.tabs.ratings')" milestone="P2.8" :module="__('admin.customers.modules.ratings')" /></x-admin.tabs.panel>

            <x-admin.tabs.panel id="consents" :url="route('admin.customers.tabs.consents', $customer)" />
            <x-admin.tabs.panel id="devices" :url="route('admin.customers.tabs.devices', $customer)" />
            @if ($details->canRevealContact)
                <x-admin.tabs.panel id="places" :url="route('admin.customers.tabs.saved-places', $customer)" />
            @endif
            <x-admin.tabs.panel id="data-requests" :url="route('admin.customers.tabs.deletion-requests', $customer)" />
            <x-admin.tabs.panel id="messages" :url="route('admin.customers.tabs.messages', $customer)" />
            <x-admin.tabs.panel id="audit" :url="route('admin.customers.tabs.audit', $customer)" />
        </x-admin.tabs>
    </x-admin.card>

    @can('suspend', $customer)
        <x-admin.action-modal id="customer-suspend" :title="__('admin.customers.suspend_form.title')" :message="__('admin.customers.suspend_form.message')" :action="route('admin.customers.suspend', ['customer' => $customer, 'from_show' => 1])" :submit="__('admin.customers.actions.suspend')" icon="pause">
            <x-admin.form.datetime name="expires_at" id="customer-suspend-expires" :label="__('admin.customers.fields.suspension_ends')" :hint="__('admin.customers.suspend_form.expiry_hint')" :min="now()->setTimezone($tz)->format('Y-m-d H:i')" />
        </x-admin.action-modal>
    @endcan

    @can('restrict', $customer)
        <x-admin.action-modal id="customer-restrict" :title="__('admin.customers.restrict_form.title')" :message="__('admin.customers.restrict_form.message')" :action="route('admin.customers.restrict', ['customer' => $customer, 'from_show' => 1])" :submit="__('admin.customers.actions.restrict')" variant="warning" icon="lock-closed" />
    @endcan

    @can('sendMessage', $customer)
        <x-admin.action-modal id="customer-send-message" :title="__('admin.customers.message_form.title')" :message="__('admin.customers.message_form.message')" :action="route('admin.customers.messages.store', $customer)" :submit="__('admin.customers.actions.send_message')" variant="primary" icon="inbox" :reason-hint="__('admin.customers.message_form.reason_hint')">
            <x-admin.form.input name="subject" id="customer-message-subject" :label="__('admin.customers.fields.message_subject')" maxlength="191" required />
            <x-admin.form.textarea name="body" id="customer-message-body" :label="__('admin.customers.fields.message_body')" rows="4" maxlength="2000" required />
        </x-admin.action-modal>
    @endcan
</x-admin.layouts.app>

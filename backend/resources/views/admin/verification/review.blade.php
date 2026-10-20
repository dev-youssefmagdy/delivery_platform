@php
    /** @var \App\DTOs\Provider\VerificationReview $review */
    $provider = $review->profile;
    $details = $review->details;
    $user = $details->user;
    $name = \App\DataTables\Admin\Provider\ProvidersDataTable::displayName($provider);
    $tz = (string) config('admin.display_timezone');
    $format = static fn (?\Carbon\CarbonInterface $date): ?string => $date?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
    $unsatisfied = \App\Services\Provider\DocumentChecklistService::unsatisfiedMandatory($review->providerChecklist);
    $pendingDocuments = array_values(array_filter($review->viewerItems, static fn (array $item): bool => ($item['kind'] ?? null) === 'document' && ($item['status'] ?? null) === 'pending'));
    $checklistTone = static fn (?\App\Enums\Provider\DocumentStatus $state): string => $state?->color()->value ?? 'neutral';
@endphp
<x-admin.layouts.app :title="__('admin.verification.review_title')">
    <x-admin.page-header
        :title="$name"
        :subtitle="__('admin.verification.review_subtitle', ['uuid' => $provider->uuid])"
        :breadcrumbs="[['label' => __('admin.verification.title'), 'url' => route('admin.verification.index')], ['label' => $name]]"
    >
        <x-admin.button variant="secondary" icon="user" :href="route('admin.providers.show', $provider)">{{ __('admin.verification.actions.open_profile') }}</x-admin.button>
        @can('claim', $provider)
            <x-admin.confirm-action :url="route('admin.verification.claim', ['provider' => $provider])" :title="__('admin.verification.confirm.claim_title')" :message="__('admin.verification.confirm.claim_message')" :confirm-label="__('admin.verification.actions.claim')" variant="primary" icon="user">{{ __('admin.verification.actions.claim') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @unless ($provider->status->isAwaitingReview())
        <x-admin.alert type="warning" class="mb-6" :title="$provider->status->label()">{{ __('admin.verification.not_in_review') }}</x-admin.alert>
    @elseif ($provider->status === \App\Enums\Provider\ProviderStatus::MoreInformationRequired)
        <x-admin.alert type="info" class="mb-6" :title="$provider->status->label()">
            {{ __('admin.verification.waiting_for_provider') }}
            @if ($provider->status_reason)
                <span class="block">{{ __('admin.providers.notices.reason', ['reason' => $provider->status_reason]) }}</span>
            @endif
        </x-admin.alert>
    @endunless

    <div class="grid gap-6 xl:grid-cols-2" data-review-screen>
        <x-admin.card :title="__('admin.verification.documents_title')" :subtitle="__('admin.verification.documents_subtitle')">
            <x-admin.document-viewer :items="$review->viewerItems" />
        </x-admin.card>

        <div class="space-y-6">
            <x-admin.card :title="__('admin.verification.applicant')">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.providers.fields.name')" :value="$user->name" />
                    <x-admin.detail-list.item :label="__('admin.providers.fields.phone')"><span class="ltr-only">{{ $review->maskedPhone }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.providers.fields.provider_type')" :value="$provider->provider_type?->label()" />
                    <x-admin.detail-list.item :label="__('admin.providers.fields.city')" :value="$provider->city?->name" />
                    <x-admin.detail-list.item :label="__('admin.providers.fields.status')"><x-admin.badge :value="$provider->status" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.providers.fields.submitted')" :value="$format($provider->submitted_at)" />
                    <x-admin.detail-list.item :label="__('admin.verification.fields.assigned')" :value="$provider->assignedAdmin?->name" />
                </x-admin.detail-list>
            </x-admin.card>

            <x-admin.card :title="__('admin.verification.checklist_title')" :subtitle="__('admin.verification.checklist_subtitle')">
                <ul class="divide-y divide-slate-100" data-review-checklist>
                    @forelse ($review->providerChecklist as $item)
                        <li class="flex items-center justify-between gap-3 py-2 text-sm">
                            <span>{{ $item->type->name }} @unless ($item->mandatory)<span class="text-xs text-slate-500">({{ __('admin.document_types.optional') }})</span>@endunless</span>
                            <x-admin.badge :label="$item->state?->label() ?? __('admin.verification.missing')" :color="$checklistTone($item->state)" />
                        </li>
                    @empty
                        <li class="py-2 text-sm text-slate-500">{{ __('admin.verification.no_requirements') }}</li>
                    @endforelse
                </ul>

                @foreach ($review->vehicleChecklists as $entry)
                    @php($vehicle = $entry['vehicle'])
                    <div class="mt-5 rounded-(--radius-control) border border-slate-200 p-3" data-review-vehicle>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold"><span class="ltr-only font-mono">{{ $vehicle->plate_display }}</span> · {{ $vehicle->make }} {{ $vehicle->model }} {{ $vehicle->year }}</p>
                            <div class="flex items-center gap-2">
                                <x-admin.badge :value="$vehicle->status" />
                                <x-admin.button variant="secondary" size="sm" icon="eye" :href="route('admin.vehicles.show', $vehicle)">{{ __('admin.common.view') }}</x-admin.button>
                                @can('approve', $vehicle)
                                    <x-admin.confirm-action :url="route('admin.vehicles.approve', ['vehicle' => $vehicle, 'from_show' => 1])" :title="__('admin.vehicles.confirm.approve_title')" :message="__('admin.vehicles.confirm.approve_message')" :confirm-label="__('admin.vehicles.actions.approve')" variant="success" icon="check-circle">{{ __('admin.vehicles.actions.approve') }}</x-admin.confirm-action>
                                @endcan
                                @can('reject', $vehicle)
                                    <x-admin.confirm-action :url="route('admin.vehicles.reject', ['vehicle' => $vehicle, 'from_show' => 1])" :title="__('admin.vehicles.confirm.reject_title')" :message="__('admin.vehicles.confirm.reject_message')" :confirm-label="__('admin.vehicles.actions.reject')" reason-required icon="x-mark">{{ __('admin.vehicles.actions.reject') }}</x-admin.confirm-action>
                                @endcan
                            </div>
                        </div>
                        <ul class="mt-2 divide-y divide-slate-100">
                            @foreach ($entry['items'] as $item)
                                <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                    <span>{{ $item->type->name }} @unless ($item->mandatory)<span class="text-xs text-slate-500">({{ __('admin.document_types.optional') }})</span>@endunless</span>
                                    <x-admin.badge :label="$item->state?->label() ?? __('admin.verification.missing')" :color="$checklistTone($item->state)" />
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </x-admin.card>

            <x-admin.card :title="__('admin.verification.services_title')">
                <ul class="divide-y divide-slate-100">
                    @forelse ($review->services as $service)
                        <li class="flex items-center justify-between gap-3 py-2 text-sm">
                            <span>{{ $service->serviceCategory->name }}</span>
                            <x-admin.badge :value="$service->status" />
                        </li>
                    @empty
                        <li class="py-2 text-sm text-slate-500">{{ __('admin.verification.no_services') }}</li>
                    @endforelse
                </ul>
            </x-admin.card>

            <x-admin.card :title="__('admin.verification.decision_title')" :subtitle="__('admin.verification.decision_subtitle')">
                @if ($unsatisfied !== [])
                    <x-admin.alert type="warning" class="mb-4">{{ trans_choice('admin.verification.documents_outstanding', count($unsatisfied), ['count' => count($unsatisfied)]) }}</x-admin.alert>
                @endif
                <div class="flex flex-wrap gap-2">
                    @can('verify', $provider)
                        @if ($unsatisfied === [])
                            <x-admin.button variant="success" icon="check-circle" data-modal-open="review-approve" data-review-approve>{{ __('admin.verification.actions.approve') }}</x-admin.button>
                        @else
                            <x-admin.button variant="success" icon="check-circle" disabled data-review-approve>{{ __('admin.verification.actions.approve') }}</x-admin.button>
                        @endif
                        <x-admin.button variant="warning" icon="inbox" data-modal-open="review-more-info" data-review-more-info>{{ __('admin.verification.actions.request_information') }}</x-admin.button>
                    @endcan
                    @can('reject', $provider)
                        <x-admin.button variant="danger" icon="x-mark" data-modal-open="review-reject" data-review-reject>{{ __('admin.verification.actions.reject') }}</x-admin.button>
                    @endcan
                    @cannot('verify', $provider)
                        @cannot('reject', $provider)
                            <p class="text-sm text-slate-500">{{ __('admin.verification.read_only') }}</p>
                        @endcannot
                    @endcannot
                </div>
            </x-admin.card>
        </div>
    </div>

    @can('verify', $provider)
        <x-admin.modal id="review-approve" :title="__('admin.verification.approve_form.title')" size="md">
            <x-admin.form :action="route('admin.verification.approve', $provider)" method="POST">
                <p class="mb-4 text-sm text-slate-600">{{ __('admin.verification.approve_form.message') }}</p>
                <div class="space-y-2">
                    @foreach ($review->services as $service)
                        @if ($service->status !== \App\Enums\Provider\ServiceApprovalStatus::Rejected)
                            <x-admin.form.checkbox name="approved_services[]" :id="'review-service-'.$service->service_category_id" :value="$service->service_category_id" :label="(string) $service->serviceCategory->name" :checked="true" :unchecked-value="null" />
                        @endif
                    @endforeach
                </div>
                <div class="mt-4">
                    <x-admin.form.textarea name="note" id="review-approve-note" :label="__('admin.providers.fields.note')" :hint="__('admin.verification.approve_form.note_hint')" rows="3" :maxlength="config('admin.reason.max')" />
                </div>
                <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5">
                    <x-admin.button variant="secondary" x-on:click="$dispatch('admin-modal:close', { id: 'review-approve' })">{{ __('admin.common.cancel') }}</x-admin.button>
                    <x-admin.button type="submit" variant="success" icon="check-circle">{{ __('admin.verification.actions.approve') }}</x-admin.button>
                </div>
            </x-admin.form>
        </x-admin.modal>

        <x-admin.action-modal id="review-more-info" :title="__('admin.verification.more_info_form.title')" :message="__('admin.verification.more_info_form.message')" :action="route('admin.verification.request-information', $provider)" :submit="__('admin.verification.actions.request_information')" variant="warning" icon="inbox" :reason-hint="__('admin.verification.more_info_form.reason_hint')">
            @foreach ($pendingDocuments as $item)
                <x-admin.form.input :name="'document_issues['.$item['id'].']'" :id="'review-issue-'.$item['id']" :label="$item['group'].' · '.$item['label']" :hint="__('admin.verification.more_info_form.document_hint')" maxlength="1000" />
            @endforeach
        </x-admin.action-modal>
    @endcan

    @can('reject', $provider)
        <x-admin.action-modal id="review-reject" :title="__('admin.verification.reject_form.title')" :message="__('admin.verification.reject_form.message')" :action="route('admin.verification.reject', $provider)" :submit="__('admin.verification.actions.reject')" icon="x-mark" :reason-hint="__('admin.verification.reject_form.reason_hint')" />
    @endcan
</x-admin.layouts.app>

@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
@endphp
<x-admin.layouts.app :title="__('leads.show_title').' '.$lead->reference">
    <x-admin.page-header
        :title="$lead->reference"
        :subtitle="$lead->name"
        :breadcrumbs="[['label' => __('leads.title'), 'url' => route('admin.leads.index')], ['label' => $lead->reference]]"
    >
        @foreach ($lead->status->next() as $target)
            @can('transition', [$lead, $target])
                <x-admin.confirm-action
                    :url="route('admin.leads.transition', [$lead, 'status' => $target->value])"
                    :title="__('leads.confirm.title')"
                    :message="__('leads.confirm.message')"
                    :confirm-label="__('leads.actions.'.$target->value)"
                    :variant="in_array($target, [\App\Enums\Lead\LeadStatus::Rejected, \App\Enums\Lead\LeadStatus::Spam], true) ? 'danger' : 'secondary'"
                    :reason-required="$target->needsReason()"
                >{{ __('leads.actions.'.$target->value) }}</x-admin.confirm-action>
            @endcan
        @endforeach
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card :title="__('admin.common.details')">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('leads.fields.name')" :value="$lead->name" />
                    <x-admin.detail-list.item :label="__('leads.fields.phone')"><a dir="ltr" class="text-brand-700 hover:underline" href="tel:{{ $lead->phone_e164 }}">{{ $lead->phone_e164 }}</a></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('leads.fields.email')">
                        @if ($lead->email)<a dir="ltr" class="text-brand-700 hover:underline" href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>@else — @endif
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('leads.fields.city')" :value="$lead->city?->name ?? $lead->city_other ?? '—'" />
                    <x-admin.detail-list.item :label="__('leads.fields.service')" :value="$lead->serviceCategory?->name ?? '—'" />
                    <x-admin.detail-list.item :label="__('leads.fields.vehicle')" :value="$lead->vehicle_note ?? '—'" />
                    <x-admin.detail-list.item :label="__('leads.fields.message')"><span class="whitespace-pre-line">{{ $lead->message ?? '—' }}</span></x-admin.detail-list.item>
                </x-admin.detail-list>
            </x-admin.card>

            @can('update', $lead)
                <x-admin.card :title="__('leads.fields.notes')" :subtitle="__('leads.notes_hint')">
                    <x-admin.form :action="route('admin.leads.update', $lead)" method="PUT">
                        <input type="hidden" name="lock_version" value="{{ $lead->lock_version }}">
                        <x-admin.form.remote-select name="assigned_admin_id" lookup="admins" :label="__('leads.fields.owner')" :selected="$lead->assignedAdmin ? [$lead->assignedAdmin->id => $lead->assignedAdmin->name] : []" />
                        <x-admin.form.textarea name="notes" :label="__('leads.fields.notes')" :value="$lead->notes" rows="5" maxlength="5000" />
                        <x-admin.form.actions />
                    </x-admin.form>
                </x-admin.card>
            @endcan

            <x-admin.card :title="__('admin.common.activity')">
                @include('admin.partials.activity', ['logs' => $logs])
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card :title="__('leads.fields.status')">
                <x-admin.detail-list :cols="1">
                    <x-admin.detail-list.item :label="__('leads.fields.status')"><x-admin.badge :value="$lead->status" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('leads.fields.status_reason')" :value="$lead->status_reason ?? '—'" />
                    <x-admin.detail-list.item :label="__('leads.fields.owner')" :value="$lead->assignedAdmin?->name ?? '—'" />
                    <x-admin.detail-list.item :label="__('leads.fields.created')" :value="$when($lead->created_at)" />
                    <x-admin.detail-list.item :label="__('leads.fields.language')" :value="strtoupper($lead->locale)" />
                    <x-admin.detail-list.item :label="__('leads.fields.consent')" :value="$when($lead->consented_at)" />
                    <x-admin.detail-list.item :label="__('leads.fields.source')" :value="$lead->source" />
                </x-admin.detail-list>
            </x-admin.card>
        </div>
    </div>
</x-admin.layouts.app>

@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
    $booking = $incident->booking;
    $canEvidence = auth('admin')->user()?->can('viewEvidence', $incident);
    $tabs = [
        'timeline' => ['label' => __('safety.admin.tabs.timeline'), 'icon' => 'clock'],
        'evidence' => ['label' => __('safety.admin.tabs.evidence'), 'icon' => 'lock-closed'],
        'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clipboard'],
    ];
    $items = $events->map(fn ($event): array => [
        'title' => $event->type->label(),
        'time' => $when($event->created_at),
        'description' => collect((array) $event->meta)->only(['from', 'to', 'outcome', 'trigger', 'category'])->filter(fn ($value) => is_scalar($value) && $value !== '')->map(fn ($value, $key) => $key.': '.$value)->implode(' · ') ?: null,
        'badge' => null,
        'actor' => $event->actor_type->label(),
        'reason' => null,
    ])->values()->all();
@endphp
<x-admin.layouts.app :title="$incident->number">
    <x-admin.page-header
        :title="$incident->number.' · '.$incident->category->label()"
        :subtitle="$incident->trigger->label().' · '.$incident->reporter_type->label()"
        :breadcrumbs="[['label' => __('safety.admin.title'), 'url' => route('admin.safety-incidents.index')], ['label' => $incident->number]]"
    >
        @can('acknowledge', $incident)
            <x-admin.confirm-action :url="route('admin.safety-incidents.acknowledge', $incident)" :title="__('safety.admin.confirm.acknowledge_title')" :message="__('safety.admin.confirm.acknowledge_message')" :confirm-label="__('safety.admin.actions.acknowledge')" variant="primary" icon="check">{{ __('safety.admin.actions.acknowledge') }}</x-admin.confirm-action>
        @endcan
        @can('reopen', $incident)
            <x-admin.confirm-action :url="route('admin.safety-incidents.reopen', $incident)" :title="__('safety.admin.confirm.reopen_title')" :message="__('safety.admin.confirm.reopen_message')" :confirm-label="__('safety.admin.actions.reopen')" reason-required variant="warning" icon="refresh">{{ __('safety.admin.actions.reopen') }}</x-admin.confirm-action>
        @endcan
        @can('exportEvidence', $incident)
            <x-admin.button variant="secondary" icon="download" :href="route('admin.safety-incidents.bundle', $incident)">{{ __('safety.admin.actions.export_bundle') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    @if ($incident->legal_hold)
        <x-admin.alert type="warning" class="mb-4">{{ __('safety.admin.hold_notice') }}</x-admin.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card :padding="false" class="p-5">
                <x-admin.tabs :tabs="$tabs">
                    <x-admin.tabs.panel id="timeline">
                        <p class="mb-4 text-sm text-slate-500">{{ __('safety.admin.timeline_hint') }}</p>
                        <x-admin.timeline :items="$items" />
                    </x-admin.tabs.panel>
                    @if ($canEvidence)
                        <x-admin.tabs.panel id="evidence" :url="route('admin.safety-incidents.evidence', $incident)" />
                    @else
                        <x-admin.tabs.panel id="evidence"><x-admin.empty-state icon="lock-closed" :title="__('safety.admin.evidence_forbidden')" compact /></x-admin.tabs.panel>
                    @endif
                    <x-admin.tabs.panel id="activity" :url="route('admin.safety-incidents.activity', $incident)" />
                </x-admin.tabs>
            </x-admin.card>

            @can('message', $incident)
                <x-admin.card :title="__('safety.admin.message.title')" :subtitle="__('safety.admin.message.subtitle')">
                    <x-admin.form :action="route('admin.safety-incidents.message', $incident)" method="POST">
                        <x-admin.form.textarea name="body" :label="__('safety.admin.fields.message')" rows="4" required maxlength="2000" />
                        <x-admin.form.actions :submit="__('safety.admin.message.send')" submit-icon="check" />
                    </x-admin.form>
                </x-admin.card>
            @endcan
            @can('note', $incident)
                <x-admin.card :title="__('safety.admin.note.title')" :subtitle="__('safety.admin.note.subtitle')">
                    <x-admin.form :action="route('admin.safety-incidents.note', $incident)" method="POST">
                        <x-admin.form.textarea name="body" :label="__('safety.admin.fields.note')" rows="3" required maxlength="4000" />
                        <x-admin.form.actions :submit="__('safety.admin.note.save')" submit-icon="check" />
                    </x-admin.form>
                </x-admin.card>
            @endcan
        </div>

        <div class="space-y-6">
            <x-admin.card :title="__('admin.common.details')">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('safety.admin.fields.status')"><x-admin.badge :value="$incident->status" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('safety.admin.fields.severity')"><x-admin.badge :value="$incident->severity" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('safety.admin.fields.priority')"><x-admin.badge :value="$incident->priority" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('safety.admin.fields.assignee')" :value="$incident->assignedAdmin?->name" />
                    <x-admin.detail-list.item :label="__('safety.admin.fields.sla')">
                        {{ $when($incident->sla_due_at) ?? '—' }}
                        @if ($incident->sla_breached_at !== null || $incident->isSlaBreached())
                            <x-admin.badge :label="__('safety.admin.sla_breached')" color="danger" />
                        @elseif ($incident->acknowledged_at !== null)
                            <x-admin.badge :label="__('safety.admin.sla_met')" color="success" />
                        @endif
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('safety.admin.fields.occurred_at')" :value="$when($incident->occurred_at)" />
                    <x-admin.detail-list.item :label="__('safety.admin.fields.acknowledged_at')" :value="$when($incident->acknowledged_at)" />
                    @if ($incident->escalation_level)
                        <x-admin.detail-list.item :label="__('safety.admin.fields.escalation_level')" :value="$incident->escalation_level->label()" />
                    @endif
                    @if ($incident->outcome)
                        <x-admin.detail-list.item :label="__('safety.admin.fields.outcome')" :value="$incident->outcome->label()" />
                    @endif
                    <x-admin.detail-list.item :label="__('safety.admin.legal_hold')" :value="$incident->legal_hold ? __('admin.common.yes') : __('admin.common.no')" />
                </x-admin.detail-list>
            </x-admin.card>

            <x-admin.card :title="__('safety.admin.linked.title')">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('safety.admin.linked.reporter')">
                        @can('view', $incident->reporter)
                            <a class="text-brand-700 hover:underline" href="{{ route('admin.customers.show', $incident->reporter->uuid) }}">{{ $incident->reporter->name }}</a>
                        @else
                            {{ $incident->reporter->name }}
                        @endcan
                        · {{ $incident->reporter_type->label() }}
                    </x-admin.detail-list.item>
                    @if ($booking)
                        <x-admin.detail-list.item :label="__('safety.admin.fields.booking')">
                            @can('view', $booking)
                                <a class="font-medium text-brand-700 hover:underline" href="{{ route('admin.bookings.show', $booking->uuid) }}">{{ $booking->code }}</a>
                            @else
                                {{ $booking->code }}
                            @endcan
                            · <x-admin.badge :value="$booking->status" />
                        </x-admin.detail-list.item>
                    @endif
                </x-admin.detail-list>
            </x-admin.card>

            @canany(['assign', 'triage', 'escalate', 'resolve', 'legalHold'], $incident)
                <x-admin.card :title="__('safety.admin.actions.title')">
                    <div class="space-y-6">
                        @can('assign', $incident)
                            <x-admin.form :action="route('admin.safety-incidents.assign', $incident)" method="POST">
                                <x-admin.form.select name="admin_id" :label="__('safety.admin.fields.assignee')" :options="collect($assignees)->mapWithKeys(fn ($admin) => [$admin->id => $admin->name])->all()" :value="$incident->assigned_admin_id" :placeholder="__('safety.admin.unassigned')" />
                                <x-admin.form.actions :submit="__('safety.admin.actions.assign')" submit-icon="user" class="mt-3 pt-3" />
                            </x-admin.form>
                        @endcan
                        @can('triage', $incident)
                            <x-admin.form :action="route('admin.safety-incidents.triage', $incident)" method="POST">
                                <x-admin.form.enum-select name="severity" :label="__('safety.admin.fields.severity')" :enum="\App\Enums\Safety\IncidentSeverity::class" :value="$incident->severity" required />
                                <x-admin.form.enum-select name="priority" :label="__('safety.admin.fields.priority')" :enum="\App\Enums\Safety\IncidentPriority::class" :value="$incident->priority" required />
                                <x-admin.form.textarea name="reason" :label="__('admin.common.reason')" rows="2" required />
                                <x-admin.form.actions :submit="__('safety.admin.actions.triage')" class="mt-3 pt-3" />
                            </x-admin.form>
                        @endcan
                        @can('escalate', $incident)
                            <x-admin.form :action="route('admin.safety-incidents.escalate', $incident)" method="POST" :confirm="__('safety.admin.confirm.escalate_message')" :confirm-title="__('safety.admin.confirm.escalate_title')">
                                <x-admin.form.enum-select name="level" :label="__('safety.admin.fields.escalation_level')" :enum="\App\Enums\Safety\IncidentEscalationLevel::class" required />
                                <x-admin.form.textarea name="reason" :label="__('admin.common.reason')" rows="3" required />
                                <x-admin.form.actions :submit="__('safety.admin.actions.escalate')" submit-icon="bolt" class="mt-3 pt-3" />
                            </x-admin.form>
                        @endcan
                        @can('resolve', $incident)
                            <x-admin.form :action="route('admin.safety-incidents.resolve', $incident)" method="POST" :confirm="__('safety.admin.confirm.resolve_message')" :confirm-title="__('safety.admin.confirm.resolve_title')">
                                <x-admin.form.enum-select name="outcome" :label="__('safety.admin.fields.outcome')" :enum="\App\Enums\Safety\IncidentOutcome::class" required />
                                <x-admin.form.textarea name="reason" :label="__('safety.admin.resolve.reason')" :hint="__('safety.admin.resolve.reason_hint')" rows="3" required />
                                <x-admin.form.textarea name="public_message" :label="__('safety.admin.fields.public_message')" :hint="__('safety.admin.resolve.public_hint')" rows="2" maxlength="1000" />
                                <x-admin.form.actions :submit="__('safety.admin.actions.resolve')" class="mt-3 pt-3" />
                            </x-admin.form>
                        @endcan
                        @can('legalHold', $incident)
                            <x-admin.form :action="route('admin.safety-incidents.legal-hold', $incident)" method="POST">
                                <input type="hidden" name="hold" value="{{ $incident->legal_hold ? 0 : 1 }}">
                                <x-admin.form.textarea name="reason" :label="__('admin.common.reason')" :hint="__('safety.admin.hold_hint')" rows="2" required />
                                <x-admin.form.actions :submit="$incident->legal_hold ? __('safety.admin.actions.release_hold') : __('safety.admin.actions.set_hold')" submit-icon="lock-closed" class="mt-3 pt-3" />
                            </x-admin.form>
                        @endcan
                    </div>
                </x-admin.card>
            @endcanany
        </div>
    </div>
</x-admin.layouts.app>

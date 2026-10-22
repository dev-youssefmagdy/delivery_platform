@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
    $booking = $ticket->booking;
    $owner = $ticket->user;
    $replyLocale = in_array($owner->locale ?? null, ['ar', 'en'], true) ? $owner->locale : 'ar';
    $tabs = [
        'thread' => ['label' => __('admin.support_tickets.tabs.thread'), 'icon' => 'inbox'],
        'history' => ['label' => __('admin.support_tickets.tabs.history'), 'icon' => 'clock'],
        'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clipboard'],
    ];
    if ($booking !== null) {
        $tabs['chat'] = ['label' => __('admin.support_tickets.tabs.chat'), 'icon' => 'lock-closed'];
    }
@endphp
<x-admin.layouts.app :title="$ticket->number">
    <x-admin.page-header
        :title="$ticket->number.' · '.$ticket->subject"
        :subtitle="$ticket->category->label().' · '.$ticket->app->label()"
        :breadcrumbs="[['label' => __('admin.support_tickets.title'), 'url' => route('admin.support-tickets.index')], ['label' => $ticket->number]]"
    >
        @can('claim', $ticket)
            <x-admin.confirm-action :url="route('admin.support-tickets.claim', $ticket)" :title="__('admin.support_tickets.confirm.claim_title')" :message="__('admin.support_tickets.confirm.claim_message')" :confirm-label="__('admin.support_tickets.actions.claim')" variant="primary" icon="user">{{ __('admin.support_tickets.actions.claim') }}</x-admin.confirm-action>
        @endcan
        @can('reopen', $ticket)
            <x-admin.confirm-action :url="route('admin.support-tickets.reopen', $ticket)" :title="__('admin.support_tickets.confirm.reopen_title')" :message="__('admin.support_tickets.confirm.reopen_message')" :confirm-label="__('admin.support_tickets.actions.reopen')" reason-required variant="warning" icon="refresh">{{ __('admin.support_tickets.actions.reopen') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card :padding="false" class="p-5">
                <x-admin.tabs :tabs="$tabs">
                    <x-admin.tabs.panel id="thread">
                        @include('admin.support-tickets._thread', ['ticket' => $ticket, 'thread' => $thread, 'when' => $when])
                    </x-admin.tabs.panel>
                    <x-admin.tabs.panel id="history">
                        @include('admin.support-tickets._events', ['events' => $events, 'when' => $when])
                    </x-admin.tabs.panel>
                    <x-admin.tabs.panel id="activity" :url="route('admin.support-tickets.activity', $ticket)" />
                    @if ($booking !== null)
                        @can('viewChat', $ticket)
                            <x-admin.tabs.panel id="chat" :url="route('admin.support-tickets.chat', $ticket)" />
                        @else
                            <x-admin.tabs.panel id="chat"><x-admin.empty-state icon="lock-closed" :title="__('admin.support_tickets.chat.forbidden')" compact /></x-admin.tabs.panel>
                        @endcan
                    @endif
                </x-admin.tabs>
            </x-admin.card>

            @can('reply', $ticket)
                <x-admin.card :title="__('admin.support_tickets.reply.title')" :subtitle="__('admin.support_tickets.reply.subtitle')">
                    <x-admin.form :action="route('admin.support-tickets.reply', $ticket)" method="POST">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-admin.form.select name="visibility" :label="__('admin.support_tickets.fields.visibility')" :hint="__('admin.support_tickets.reply.visibility_hint')" :options="collect(\App\Enums\Support\TicketVisibility::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all()" value="public" required />
                            @if ($macros->isNotEmpty())
                                <div x-data="{ macros: @js($macros->mapWithKeys(fn ($macro) => [$macro->uuid => $macro->bodyFor($replyLocale)])->all()) }">
                                    <x-admin.form.select name="macro_id" :label="__('admin.support_tickets.fields.macro')" :hint="__('admin.support_tickets.reply.macro_hint')" :options="$macros->mapWithKeys(fn ($macro) => [$macro->uuid => $macro->titleFor($replyLocale)])->all()" :placeholder="__('admin.support_tickets.reply.no_macro')"
                                        x-on:change="if ($event.target.value && macros[$event.target.value]) { const box = document.getElementById('reply-body'); box.value = macros[$event.target.value]; box.dispatchEvent(new Event('input', { bubbles: true })); }" />
                                </div>
                            @endif
                        </div>
                        <x-admin.form.textarea name="body" id="reply-body" :label="__('admin.support_tickets.fields.message')" rows="5" required maxlength="{{ (int) config('support.tickets.body_max_chars', 4000) }}" />
                        <x-admin.form.file name="attachment" :label="__('admin.support_tickets.fields.attachment')" accept="image/jpeg,image/png,image/webp,application/pdf" :max-kb="(int) config('support.tickets.attachment_max_kilobytes', 8192)" />
                        <x-admin.form.actions :submit="__('admin.support_tickets.reply.send')" submit-icon="check" />
                    </x-admin.form>
                </x-admin.card>
            @endcan
        </div>

        <div class="space-y-6">
            <x-admin.card :title="__('admin.common.details')">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.support_tickets.fields.status')"><x-admin.badge :value="$ticket->status" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.support_tickets.fields.priority')"><x-admin.badge :value="$ticket->priority" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.support_tickets.fields.group')" :value="$ticket->group->label()" />
                    <x-admin.detail-list.item :label="__('admin.support_tickets.fields.assignee')" :value="$ticket->assignedAdmin?->name" />
                    <x-admin.detail-list.item :label="__('admin.support_tickets.fields.requester')">
                        @can('view', $owner)
                            <a class="text-brand-700 hover:underline" href="{{ route('admin.customers.show', $owner->uuid) }}">{{ $owner->name }}</a>
                        @else
                            {{ $owner->name }}
                        @endcan
                        · {{ $ticket->app->label() }}
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.support_tickets.fields.source')" :value="$ticket->source->label()" />
                    <x-admin.detail-list.item :label="__('admin.support_tickets.fields.sla_due_at')">
                        {{ $when($ticket->sla_due_at) ?? '—' }}
                        @if ($ticket->sla_breached_at !== null || $ticket->isSlaBreached())
                            <x-admin.badge :label="__('admin.support_tickets.sla_breached')" color="danger" />
                        @elseif ($ticket->first_response_at !== null)
                            <x-admin.badge :label="__('admin.support_tickets.sla_met')" color="success" />
                        @endif
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.support_tickets.fields.first_response_at')" :value="$when($ticket->first_response_at)" />
                    <x-admin.detail-list.item :label="__('admin.common.created_at')" :value="$when($ticket->created_at)" />
                    @if ($ticket->resolution_reason)
                        <x-admin.detail-list.item :label="__('admin.support_tickets.fields.resolution')" :value="$ticket->resolution_reason" full />
                    @endif
                </x-admin.detail-list>
            </x-admin.card>

            @include('admin.support-tickets._booking-panel', ['ticket' => $ticket, 'booking' => $booking, 'when' => $when])
            @include('admin.support-tickets._finance-panel', ['ticket' => $ticket, 'payments' => $payments])

            @canany(['assign', 'changePriority', 'escalate', 'resolve'], $ticket)
                <x-admin.card :title="__('admin.support_tickets.actions.title')">
                    <div class="space-y-6">
                        @can('assign', $ticket)
                            <x-admin.form :action="route('admin.support-tickets.assign', $ticket)" method="POST">
                                <x-admin.form.select name="admin_id" :label="__('admin.support_tickets.fields.assignee')" :options="collect($assignees)->mapWithKeys(fn ($admin) => [$admin->id => $admin->name])->all()" :value="$ticket->assigned_admin_id" :placeholder="__('admin.support_tickets.unassigned')" />
                                <x-admin.form.actions :submit="__('admin.support_tickets.actions.assign')" submit-icon="user" class="mt-3 pt-3" />
                            </x-admin.form>
                        @endcan
                        @can('changePriority', $ticket)
                            <x-admin.form :action="route('admin.support-tickets.priority', $ticket)" method="POST">
                                <x-admin.form.enum-select name="priority" :label="__('admin.support_tickets.fields.priority')" :enum="\App\Enums\Support\TicketPriority::class" :value="$ticket->priority" required />
                                <x-admin.form.actions :submit="__('admin.support_tickets.actions.set_priority')" class="mt-3 pt-3" />
                            </x-admin.form>
                        @endcan
                        @can('escalate', $ticket)
                            <x-admin.form :action="route('admin.support-tickets.escalate', $ticket)" method="POST" :confirm="__('admin.support_tickets.confirm.escalate_message')" :confirm-title="__('admin.support_tickets.confirm.escalate_title')">
                                <x-admin.form.select name="group" :label="__('admin.support_tickets.escalate.to')" :options="collect(\App\Enums\Support\TicketGroup::escalationTargets())->mapWithKeys(fn ($group) => [$group->value => $group->label()])->all()" required />
                                <x-admin.form.textarea name="reason" :label="__('admin.common.reason')" :hint="__('admin.support_tickets.escalate.reason_hint')" rows="3" required />
                                <x-admin.form.actions :submit="__('admin.support_tickets.actions.escalate')" submit-icon="bolt" class="mt-3 pt-3" />
                            </x-admin.form>
                        @endcan
                        @can('resolve', $ticket)
                            <x-admin.form :action="route('admin.support-tickets.resolve', $ticket)" method="POST" :confirm="__('admin.support_tickets.confirm.resolve_message')" :confirm-title="__('admin.support_tickets.confirm.resolve_title')">
                                <x-admin.form.textarea name="reason" :label="__('admin.support_tickets.resolve.reason')" :hint="__('admin.support_tickets.resolve.reason_hint')" rows="3" required />
                                <x-admin.form.actions :submit="__('admin.support_tickets.actions.resolve')" class="mt-3 pt-3" />
                            </x-admin.form>
                        @endcan
                    </div>
                </x-admin.card>
            @endcanany
        </div>
    </div>
</x-admin.layouts.app>

{{-- The full thread for staff. Internal notes are visually distinct (amber, labelled) and never leave the admin panel. --}}
@if ($thread->isEmpty())
    <x-admin.empty-state icon="inbox" :title="__('admin.support_tickets.thread.empty')" compact />
@else
    <ol class="space-y-4" aria-label="{{ __('admin.support_tickets.tabs.thread') }}">
        @foreach ($thread as $message)
            @php
                $internal = $message->isInternal();
                $staff = $message->author_type === \App\Enums\Common\ActorType::Admin;
            @endphp
            <li @class([
                'rounded-(--radius-control) border p-4',
                'border-amber-300 border-dashed bg-amber-50' => $internal,
                'border-brand-200 bg-brand-50' => ! $internal && $staff,
                'border-slate-200 bg-white' => ! $internal && ! $staff,
            ]) data-message-visibility="{{ $message->visibility->value }}">
                <div class="mb-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <span class="font-semibold text-slate-800">
                        @if ($staff)
                            {{ $message->authorAdmin?->name ?? __('admin.support_tickets.thread.system') }}
                        @else
                            {{ $ticket->user->name }}
                        @endif
                    </span>
                    <span>{{ $message->author_type->label() }}</span>
                    @if ($internal)
                        <x-admin.badge :label="__('admin.support_tickets.thread.internal_note')" color="warning" />
                    @endif
                    <span class="ms-auto">{{ $when($message->created_at) }}</span>
                </div>
                <p dir="auto" class="text-sm whitespace-pre-line text-slate-900">{{ $message->body }}</p>
                @if ($message->attachments->isNotEmpty())
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($message->attachments as $attachment)
                            <li>
                                <a href="{{ route('admin.support-tickets.attachments.show', [$ticket, $attachment->uuid]) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-(--radius-control) border border-slate-200 bg-white px-2.5 py-1 text-xs text-brand-700 hover:underline">
                                    <x-admin.icon name="document" class="size-4" />
                                    {{ $attachment->original_name ?: __('admin.support_tickets.thread.attachment') }}
                                    <span class="text-slate-400">· {{ $attachment->scan_status->label() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ol>
@endif

@php
    $tz = (string) config('admin.display_timezone', 'UTC');
@endphp
{{-- Staff transcript of the booking chat (audited on every load). Images are not inlined: staff see that one exists. --}}
<p class="mb-4 text-sm text-slate-500">{{ __('admin.support_tickets.chat.intro', ['code' => $booking->code]) }}</p>
@if ($messages->isEmpty())
    <x-admin.empty-state icon="inbox" :title="__('admin.support_tickets.chat.empty')" compact />
@else
    <ol class="space-y-2">
        @foreach ($messages as $message)
            <li class="rounded-(--radius-control) border border-slate-200 p-3 text-sm">
                <div class="mb-1 flex items-center gap-2 text-xs text-slate-500">
                    <span class="font-semibold text-slate-800">{{ $message->sender_type->label() }}</span>
                    <span class="ms-auto">{{ $message->created_at->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i') }}</span>
                </div>
                @if ($message->purged_at)
                    <p class="text-slate-400">{{ __('admin.support_tickets.chat.purged') }}</p>
                @else
                    @if ($message->body)<p dir="auto" class="whitespace-pre-line text-slate-900">{{ $message->body }}</p>@endif
                    @if ($message->attachment_path)<p class="text-xs text-slate-500">{{ __('admin.support_tickets.chat.has_image') }}</p>@endif
                @endif
            </li>
        @endforeach
    </ol>
@endif

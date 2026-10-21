@props(['channel' => 'admin.operations'])
{{--
    Realtime connection health (SRS §16.6): the state of the websocket (connected / connecting / unavailable /
    disabled) and the time of the last event received on the channel. Driven by resources/js/admin/core/realtime.js.
--}}
<div
    {{ $attributes->class(['inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs text-slate-600']) }}
    data-realtime-status
    data-channel="{{ $channel }}"
    role="status"
    aria-live="polite"
>
    <span class="size-2 rounded-full bg-slate-300" data-realtime-dot aria-hidden="true"></span>
    <span data-realtime-label>{{ __('admin.realtime.idle') }}</span>
    <span class="text-slate-400" aria-hidden="true">·</span>
    <span>{{ __('admin.realtime.last_event') }}: <time data-realtime-last>{{ __('admin.realtime.none') }}</time></span>
</div>

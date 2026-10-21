@props(['table', 'seconds' => 20])
{{--
    "Live refresh" switch of a listing: when on, the table reloads every `seconds` seconds and whenever the operations feed
    reports a booking change (debounced). The choice is remembered in this browser only.
--}}
<label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700" data-live-refresh data-table="{{ $table }}" data-seconds="{{ $seconds }}">
    <input type="checkbox" class="peer sr-only" data-live-refresh-input>
    <span class="relative inline-block h-5 w-9 rounded-full bg-slate-300 transition peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 after:absolute after:start-0.5 after:top-0.5 after:size-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-4 rtl:peer-checked:after:-translate-x-4" aria-hidden="true"></span>
    <span>{{ __('admin.bookings.live_refresh') }}</span>
</label>

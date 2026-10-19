@props(['type' => 'info', 'title' => null, 'dismissible' => false])
@php
    $icon = ['info' => 'information', 'success' => 'check-circle', 'warning' => 'exclamation', 'danger' => 'x-circle'][$type] ?? 'information';
@endphp
<div {{ $attributes->class(['alert', 'alert-'.$type]) }} role="{{ $type === 'danger' ? 'alert' : 'status' }}" @if ($dismissible) x-data="{ shown: true }" x-show="shown" @endif>
    <x-admin.icon :name="$icon" class="size-5 shrink-0" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div @class(['mt-1' => $title])>{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" class="icon-btn size-6" x-on:click="shown = false" aria-label="{{ __('admin.common.close') }}">
            <x-admin.icon name="x-mark" class="size-4" />
        </button>
    @endif
</div>

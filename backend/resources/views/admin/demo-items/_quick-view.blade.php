{{-- Drawer content (loaded via AJAX). --}}
<div class="space-y-5">
    <div class="flex items-center justify-between gap-3">
        <h3 class="text-lg font-semibold text-slate-900">{{ $details->item->name }}</h3>
        <x-admin.button variant="secondary" size="sm" icon="eye" :href="route('admin.demo-items.show', $details->item)">{{ __('admin.common.view') }}</x-admin.button>
    </div>
    @if ($details->coverUrl)
        <img src="{{ $details->coverUrl }}" alt="" class="h-40 w-full rounded-(--radius-card) object-cover">
    @endif
    @include('admin.demo-items._details', ['item' => $details->item, 'coverUrl' => $details->coverUrl, 'cols' => 1])
</div>

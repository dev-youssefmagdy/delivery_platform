{{-- Live region where resources/js/admin/core/toast.js renders toasts. Server flashes (non-AJAX) are shown too. --}}
<div id="admin-toasts" aria-live="polite" aria-atomic="false" class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4 sm:items-end">
    @foreach (['success', 'error', 'warning', 'info'] as $type)
        @if (session()->has('toast.'.$type))
            <div class="toast toast-{{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}" x-data="{ shown: true }" x-show="shown" x-init="setTimeout(() => shown = false, 6000)">
                <p class="flex-1 font-medium text-slate-800">{{ session('toast.'.$type) }}</p>
                <button type="button" class="icon-btn size-6" x-on:click="shown = false" aria-label="{{ __('admin.common.close') }}">
                    <x-admin.icon name="x-mark" class="size-4" />
                </button>
            </div>
        @endif
    @endforeach
</div>

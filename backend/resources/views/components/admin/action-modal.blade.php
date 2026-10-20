@props([
    'id',
    'title',
    'action',
    'submit',
    'message' => null,
    'method' => 'POST',
    'variant' => 'danger',
    'icon' => 'exclamation',
    'reasonHint' => null,
])
{{--
    Dangerous action that needs more than a reason (suspension end, message text …): a modal holding an
    <x-admin.form> (Precognition validation before submit) with extra fields in the slot and the MANDATORY reason
    field. Open it with [data-modal-open="{{ $id }}"]. Plain "reason only" actions use <x-admin.confirm-action>.
--}}
<x-admin.modal :id="$id" :title="$title" size="md">
    <x-admin.form :action="$action" :method="$method">
        @if ($message)
            <p class="mb-4 text-sm text-slate-600">{{ $message }}</p>
        @endif
        <div class="space-y-4">
            {{ $slot }}
            <x-admin.form.textarea
                name="reason"
                :id="$id.'-reason'"
                :label="__('admin.common.reason')"
                :hint="$reasonHint ?? __('admin.confirm.reason_hint')"
                rows="3"
                required
                :minlength="config('admin.reason.min')"
                :maxlength="config('admin.reason.max')"
            />
        </div>
        <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <x-admin.button variant="secondary" x-on:click="$dispatch('admin-modal:close', { id: '{{ $id }}' })">{{ __('admin.common.cancel') }}</x-admin.button>
            <x-admin.button type="submit" :variant="$variant" :icon="$icon">{{ $submit }}</x-admin.button>
        </div>
    </x-admin.form>
</x-admin.modal>

@props(['cancel' => null, 'submit' => null, 'submitIcon' => 'check'])
{{-- Form footer: cancel link + submit button (loading/disabled state handled by ajax-form.js). --}}
<div {{ $attributes->class(['mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5']) }}>
    {{ $slot }}
    @if ($cancel)
        <x-admin.button variant="secondary" :href="$cancel">{{ __('admin.common.cancel') }}</x-admin.button>
    @endif
    <x-admin.button type="submit" :icon="$submitIcon">{{ $submit ?? __('admin.common.save') }}</x-admin.button>
</div>

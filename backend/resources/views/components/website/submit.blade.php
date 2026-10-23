@props(['label'])
<button type="submit" data-submit {{ $attributes->class(['inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-700 px-6 py-3 text-base font-semibold text-white hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto']) }}>
    <svg data-spinner class="hidden size-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
    <span data-submit-label>{{ $label }}</span>
</button>

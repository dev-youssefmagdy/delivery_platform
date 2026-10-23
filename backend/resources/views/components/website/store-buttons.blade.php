@props(['links' => [], 'app' => 'customer', 'small' => false])
{{-- Store badges from the CMS app links (only the ones an operator published; nothing is invented). --}}
@php
    $mine = collect($links)->where('app', $app)->values();
@endphp
@if ($mine->isNotEmpty())
    <ul {{ $attributes->class(['flex flex-wrap gap-2']) }}>
        @foreach ($mine as $link)
            <li>
                <a href="{{ $link['url'] }}" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg border border-slate-400/40 bg-slate-800 font-semibold text-white hover:bg-slate-700 {{ $small ? 'px-3 py-1.5 text-xs' : 'px-5 py-3 text-sm' }}">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></svg>
                    <span>{{ __('site.apps.platform.'.$link['platform']) }}</span>
                </a>
            </li>
        @endforeach
    </ul>
@endif

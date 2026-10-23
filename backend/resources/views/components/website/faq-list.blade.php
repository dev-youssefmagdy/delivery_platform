@props(['faqs', 'open' => false])
{{-- Accordion of FAQs. Native <details>: keyboard and screen-reader friendly, no script. The answer is sanitized CMS HTML (trusted); the question is escaped. --}}
<div class="divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white">
    @foreach ($faqs as $faq)
        <details class="group p-5" @if ($loop->first && $open) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-start text-base font-semibold text-slate-900 marker:hidden">
                <span>{{ $faq['question'] }}</span>
                <svg class="size-5 shrink-0 text-brand-700 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="prose-site mt-3">{!! $faq['answer_html'] !!}</div>
        </details>
    @endforeach
</div>
